<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;

trait HasFilter
{
    public function filter(Request $request = null)
    {
        $request ?: $request = request();
        
        return Filter::make()->apply($this->query(), $request);
    }
}