<aside id="{{ $id }}-filters" class="tk-filters-panel" aria-label="Table filters"
    @if($layout['filters'] === 'collapsible') hidden @endif>
    @if($layout['filters'] !== 'inline')
        <div class="tk-filter-heading">
            <strong>Filters</strong>
            @if($layout['filters'] === 'collapsible')
                <button type="button" data-tk-reset>Reset filters</button>
            @endif
        </div>
    @endif
    <div class="tk-filter-fields">
        @foreach($filterDefinitions as $filter)
            @if($filter->type === 'dateRange')
                <fieldset>
                    <legend>{{ $filter->heading }}</legend>
                    <label>From <input type="date" data-tk-filter="{{ $filter->key }}" data-tk-bound="from"
                        value="{{ $filter->initial['from'] ?? '' }}"></label>
                    <label>Through <input type="date" data-tk-filter="{{ $filter->key }}" data-tk-bound="to"
                        value="{{ $filter->initial['to'] ?? '' }}"></label>
                </fieldset>
            @else
                <label>{{ $filter->heading }}
                    @if($filter->type === 'select')
                        <select aria-label="{{ $filter->heading }}" data-tk-filter="{{ $filter->key }}">
                            <option value="">{{ $filter->hint ?: 'All' }}</option>
                            @foreach($filter->options as $value => $label)
                                <option value="{{ $value }}" @if((string) $filter->initial === (string) $value) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $filter->type === 'date' ? 'date' : 'text' }}"
                            data-tk-filter="{{ $filter->key }}" placeholder="{{ $filter->hint }}"
                            value="{{ $filter->initial ?? '' }}">
                    @endif
                </label>
            @endif
        @endforeach
        @if($layout['filters'] === 'inline')
            <button type="button" class="tk-filters-reset" data-tk-reset>Reset filters</button>
        @endif
    </div>
    @if($layout['filters'] === 'sidebar')
        <div class="tk-filter-footer">
            <button type="button" class="tk-filters-reset" data-tk-reset>Reset filters</button>
        </div>
    @endif
</aside>
