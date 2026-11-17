<section class="tk tk-density-{{ $layout['density'] }} tk-filters-{{ $layout['filters'] }} tk-toolbar-{{ $layout['toolbar'] }}"
    data-tk-root data-tk-config="{{ $configJson }}" aria-label="{{ $title ?? 'Data table' }}">
    @if($title || $description)
        <header class="tk-heading">
            @if($title)<h2>{{ $title }}</h2>@endif
            @if($description)<p>{{ $description }}</p>@endif
        </header>
    @endif
    @if(in_array($layout['toolbar'], ['top', 'both']))
        @include('tablekit::components.toolbar')
    @endif
    @if($settings['selectable'])
        <div class="tk-selection" data-tk-selection aria-label="Current-page selection" hidden>
            <span><span data-tk-selected-count>0</span> selected on this page</span>
            @foreach($bulkActions as $action){!! $action->render() !!}@endforeach
        </div>
    @endif
    @if($filterDefinitions && $layout['filters'] !== 'none' && $layout['toolbar'] === 'none'
        && in_array($layout['filters'], ['collapsible', 'sidebar']))
        <button type="button" data-tk-toggle aria-controls="{{ $id }}-filters" aria-expanded="false">Filters</button>
    @endif
    <div class="tk-body">
        @if($filterDefinitions && $layout['filters'] !== 'none')
            @include('tablekit::components.filters')
        @endif
        <div class="tk-main">
            <p class="tk-status" role="status" aria-live="polite" data-tk-status></p>
            <p class="tk-error" role="alert" data-tk-error hidden></p>
            <div class="tk-scroll" tabindex="0" role="region" aria-label="Scrollable table">
                <table id="{{ $id }}" class="tk-table" style="width:100%">
                    <caption class="tk-sr-only">{{ $title ?? 'Records' }}</caption>
                    <thead><tr>
                        @if($settings['selectable'])
                            <th scope="col"><input type="checkbox" data-tk-select-page aria-label="Select current page"></th>
                        @endif
                        @foreach($columns as $column)
                            <th scope="col">{{ $column->heading }}</th>
                        @endforeach
                        @if($hasRowActions)<th scope="col">Actions</th>@endif
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @if(in_array($layout['toolbar'], ['bottom', 'both']))
        @include('tablekit::components.toolbar')
    @endif
    <noscript>JavaScript and a supported DataTables engine are required.</noscript>
</section>
