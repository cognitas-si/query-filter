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

use Cognitas\QueryFilter\Outcome\FilterIssue;
use Cognitas\QueryFilter\Outcome\FilterIssueType;
use Cognitas\QueryFilter\Outcome\FilterResult;
use Illuminate\Database\Eloquent\Builder;

class QueryFilter
{

    /**
     * The FilterUtils instance.
     *
     * @var \Cognitas\QueryFilter\Filters\FilterUtils;
     */
    protected FilterUtils $futils;

    /**
     * Create a new QueryFilter instance.
     * 
     * @param \Cognitas\QueryFilter\Filters\FilterUtils $futils
     */
    public function __construct(FilterUtils $futils)
    {
        $this->futils = $futils;
    }

    /**
     * Entry point of the filter
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array|null $filter_input
     * @return FilterResult
     */
    public function applyFilters(Builder $query, string|array|null $filter_input): FilterResult
    {
        $filter_result = new FilterResult();
        //Null check
        if(!isset($filter_input)){
            return $filter_result;
        }
        $filters = [];

        //Legacy support
        if(is_string($filter_input)){
            $filter_input = $this->futils->splitBaseFilters($filter_input);
            $filters['AND'] = $filter_input;
        }else{
            $filters = $filter_input;
        }
        
        if(isset($filters['AND'])){
            $query->where(function ($q) use($filters,$filter_result){
                $value = $filters['AND'];
                if(is_string($value)){
                    $value = $this->futils->splitBaseFilters($value);
                }
                $this->visitArray($q,$value,'AND',$filter_result);
            });
        }elseif(isset($filters['OR'])){
            $query->where(function ($q) use($filters,$filter_result){
                $value = $filters['OR'];
                if(is_string($value)){
                    $value = $this->futils->splitBaseFilters($value);
                }
                $this->visitArray($q,$value,'OR',$filter_result);
            });
        }

        if(isset($filters['ORDER'])){
            $this->applySort($query,$filters['ORDER'],$filter_result);
        }
        
        return $filter_result;
        
    }



    

    /**
     * Loops through the array and visits each element
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $array
     * @param string $op
     * @param \Cognitas\QueryFilter\Outcome\FilterResult $filter_result
     * @return void
     */
    public function visitArray(Builder $query,array $array,string $op,FilterResult $filter_result)
    {
        foreach ($array as $ele) {
            $this->visitElement($query,$ele,$op,$filter_result);
        }
    }


    /**
     * Visits and resolves the element
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $ele
     * @param string $op
     * @param \Cognitas\QueryFilter\Outcome\FilterResult $filter_result
     * @return void
     */
    public function visitElement(Builder $query,string|array $ele,string $op,FilterResult $filter_result)
    {
        $fun = ($op === 'OR')?'orWhere':'where';

        $query->$fun(function($q) use($ele,$filter_result){
            if(is_string($ele)){
                //Evaluate operand

                //Check for relation filter
                $matches = [];
                if(preg_match('/([0-9a-zA-Z]+)\.\((.*)\)/m',$ele,$matches)===1){
                    $this->futils->applyRelationOperation($q,$matches,$filter_result);
                    return;
                }

                $filter_fields = explode(':',$ele);

                if(count($filter_fields)!==3){
                    $filter_result->addIssue(new FilterIssue(FilterIssueType::SYNTAX_ERROR,$ele,"Invalid filter syntax. Expected format: field:OP:value"));
                    return;
                }

                $att = $filter_fields[0];
                $op = $filter_fields[1];
                $vals = $filter_fields[2];
                $this->futils->applyFilterOperation($q,$att,$op,$vals,$filter_result);
            }else{
                //Nested operation, extract it
                $new_key = array_keys($ele)[0];
                $nested_operation_operands = $ele[$new_key];

                //Check for short syntax
                if(gettype($nested_operation_operands)=='string'){
                    $nested_operation_operands = $this->futils->splitBaseFilters($nested_operation_operands);
                }

                //Visit operands
                $this->visitArray($q,$nested_operation_operands,$new_key,$filter_result);
            }
        });
        return;
    }

    /**
     * Applies sorting operators
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $filters
     * @param \Cognitas\QueryFilter\Outcome\FilterResult $filter_result
     * @return void
     */
    public function applySort(Builder $query,string $filters,FilterResult $filter_result)
    {
        $orders = $this->futils->splitBaseFilters($filters);
        
        foreach ($orders as $order) {
            $split_order = explode(':',$order);
            if(count($split_order)!==2){
                $filter_result->addIssue(new FilterIssue(FilterIssueType::SYNTAX_ERROR,$order,"Invalid order syntax. Expected format: field:asc or field:desc"));
                continue;
            }
            $att = $split_order[0];
            $dir = $split_order[1];
            if(in_array($dir,['asc','desc','ASC','DESC'])){
                $query->orderBy($att,$dir);
            }else{
                $filter_result->addIssue(new FilterIssue(FilterIssueType::SYNTAX_ERROR,$order,"Invalid order direction. Allowed values are: asc, desc, ASC, DESC."));
            }
        }
    }

}