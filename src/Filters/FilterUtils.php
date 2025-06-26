<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @copyright 2025 Cognitas d.o.o.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

namespace Cognitas\QueryFilter\Filters;

use Cognitas\QueryFilter\Contracts\FilterWhitelistable;
use Cognitas\QueryFilter\Outcome\FilterIssue;
use Cognitas\QueryFilter\Outcome\FilterIssueType;
use Cognitas\QueryFilter\Outcome\FilterResult;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class FilterUtils{

    /**
     * Applies filter to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $att
     * @param string $op
     * @param string|array $vals
     * @param \Cognitas\QueryFilter\Outcome\FilterResult $filter_result
     * @return void
     */
    public function applyFilterOperation(Builder $query,string $att,string $op,string|array $vals,FilterResult $filter_result)
    {
        $parsed_op = $this->getOperator($op);

        //Attribute whitelist check
        $q_model = $query->getModel();
        if(config('query-filter.attribute_whitelist') && $q_model instanceof FilterWhitelistable){
            if(!(in_array($att,$q_model->getFilterableAttributes()))){
                $filter_result->addIssue(new FilterIssue(FilterIssueType::ATTRIBUTE_WHITELIST_ERROR,$att,"Filtering by $att is not allowed."));
                return;
            }
        }

        //Handle IN,NIN
        if($parsed_op === 'IN' || $parsed_op === 'NIN'){
            $use_not = $parsed_op === 'NIN';
            $parsed_vals = explode(',',substr($vals,1,-1));
            $parsed_vals = $this->convertStringArray($parsed_vals);
            $query->whereIn($att,$parsed_vals,not:$use_not);
            return;
        }
        //Check for null
        if($vals === 'null' || $vals === 'NULL'){
            match($parsed_op){
                '=' => $query->whereNull($att),
                '!=' => $query->whereNotNull($att)
            };
        }else{
            if($vals[0]=='"' && $vals[strlen($vals)-1]=='"'){
                $vals = substr($vals,1,-1);
            }
            $query->where($att,$parsed_op,$vals);
        }
    }

    /**
     * Applies filter to related model in query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $matches
     * @param \Cognitas\QueryFilter\Outcome\FilterResult $filter_result
     * @return void
     */
    public function applyRelationOperation(Builder $query,array $matches,FilterResult $filter_result)
    {
        //There must be 2 capturing groups (+ initial match)
        if(count($matches)!==3){
            $filter_result->addIssue(new FilterIssue(FilterIssueType::SYNTAX_ERROR,implode($matches),"Invalid relation filter syntax. Expected format: relationship.(field:OP:value*...)"));
            return;
        }
        $relation = $matches[1];
        $filters = $matches[2];

        //Relation whitelist check
        $q_model = $query->getModel();
        if(config('query-filter.relation_whitelist') && $q_model instanceof FilterWhitelistable){
            if(!(in_array($relation,$q_model->getFilterableRelations()))){
                $filter_result->addIssue(new FilterIssue(FilterIssueType::RELATION_WHITELIST_ERROR,$relation,"Filtering by the relation $relation is not allowed."));
                return;
            }
        }
        

        try{
            $query->whereHas($relation, function ($q) use ($filters,$filter_result){
                $split_filters = $this->splitBaseFilters($filters,'*');
                foreach ($split_filters as $filter) {
                    $filter_fields = explode(':',$filter);
                    $att = $filter_fields[0];
                    $op = $filter_fields[1];
                    $vals = $filter_fields[2];

                    if(count($filter_fields)!==3){
                        $filter_result->addIssue(new FilterIssue(FilterIssueType::SYNTAX_ERROR,$filter,"Invalid filter syntax. Expected format: field:OP:value"));
                        continue;
                    }

                    //Check if string inside string
                    if($vals[0]=='"' && $vals[strlen($vals)-1]=='"'){
                        $vals = substr($vals,1,-1);
                    }
                    $this->applyFilterOperation($q,$att,$op,$vals,$filter_result);
                }
            });
        }catch(Throwable $e){
            $filter_result->addIssue(new FilterIssue(FilterIssueType::RELATION_NONEXISTENT,$relation,"This relation is nonexistent."));
        }
    }

    /**
     * Transforms filter operator to valid SQL operator
     * 
     * @param string $q_op
     * @return string
     */
    public function getOperator(string $q_op)
    {
        $op = '=';
        $q_op = strtoupper($q_op);
        switch($q_op){
            case 'EQ':
                $op = '=';
                break;
            case 'NEQ':
                $op = '!=';
                break;
            case 'LIKE':
                $op = 'LIKE';
                break;
            case 'NLIKE':
                $op = 'NOT LIKE';
                break;
            case 'GT':
                $op = '>';
                break;
            case 'GTE':
                $op = '>=';
                break;
            case 'LT':
                $op = '<';
                break;
            case 'LTE':
                $op = '<=';
                break;
            case 'IN':
                $op = 'IN';
                break;
            case 'NIN':
                $op = 'NIN';
                break;
            default:
                $op = '=';
        }
        return $op;
    }

    

    /**
     * Removes " from array of elements
     * 
     * @param array $arr
     * @return array
     */
    public function convertStringArray(array $arr)
    {
        $parsed_arr = [];
        foreach ($arr as $item) {
            if($item[0]=='"' && $item[strlen($item)-1]=='"'){
                $item = substr($item,1,-1);
            }
            array_push($parsed_arr,$item);
        }
        return $parsed_arr;
    }

    /**
     * Splits filter string filter1;filter2;.. into array
     * 
     * @param string $filters
     * @param string $seperator
     * @return string[]
     */
    public function splitBaseFilters(string $filters,string $seperator = ';')
    {
        $base_filters = explode($seperator,$filters);
        if(strlen($base_filters[count($base_filters)-1])===0){
            array_pop($base_filters);
        }
        return $base_filters;
    }
}