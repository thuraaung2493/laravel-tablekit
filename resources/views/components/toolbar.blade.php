<div class="tk-toolbar" role="group" aria-label="Table controls">
    @if($settings['searchable'])
        <label class="tk-search">Search
            <input type="search" data-tk-search placeholder="Search records…" autocomplete="off">
        </label>
    @endif
    @if($filterDefinitions && in_array($layout['filters'], ['collapsible', 'sidebar']))
        <button type="button" data-tk-toggle aria-controls="{{ $id }}-filters" aria-expanded="false">
            <span aria-hidden="true">☰</span> Filters <span class="tk-filter-badge" data-tk-filter-count>0</span>
        </button>
    @endif
    @foreach($headerActions as $action){!! $action->render() !!}@endforeach
    <label>Rows
        <select data-tk-length aria-label="Rows per page">
            @foreach(array_unique(array_merge($settings['page_length_options'], [$settings['page_length']])) as $length)
                @if($length <= $settings['max_page_length'])
                    <option value="{{ $length }}" @if($length === $settings['page_length']) selected @endif>{{ $length }}</option>
                @endif
            @endforeach
        </select>
    </label>
</div>
