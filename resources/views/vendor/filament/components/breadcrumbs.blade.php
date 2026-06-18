@php
    use Illuminate\View\ComponentAttributeBag;

    use function Filament\Support\generate_icon_html;
@endphp

@props([
    'breadcrumbs' => [],
])

@php
    // Prepend a Home/Dashboard root crumb so resource pages read e.g.
    // "Dashboard › Payments › List" instead of starting at the resource.
    if (filled($breadcrumbs)) {
        $homeUrl = \Filament\Facades\Filament::getUrl();

        if (filled($homeUrl) && ! array_key_exists($homeUrl, $breadcrumbs)) {
            $breadcrumbs = [$homeUrl => 'Dashboard'] + $breadcrumbs;
        }
    }
@endphp

<nav {{ $attributes->class(['fi-breadcrumbs']) }}>
    <ol class="fi-breadcrumbs-list">
        @foreach ($breadcrumbs as $url => $label)
            <li class="fi-breadcrumbs-item">
                @if (! $loop->first)
                    {{
                        generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronRight, alias: \Filament\Support\View\SupportIconAlias::BREADCRUMBS_SEPARATOR, attributes: (new ComponentAttributeBag)->class([
                            'fi-breadcrumbs-item-separator fi-ltr',
                        ]))
                    }}

                    {{
                        generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronLeft, alias: \Filament\Support\View\SupportIconAlias::BREADCRUMBS_SEPARATOR_RTL, attributes: (new ComponentAttributeBag)->class([
                            'fi-breadcrumbs-item-separator fi-rtl',
                        ]))
                    }}
                @endif

                @if (is_int($url))
                    <span class="fi-breadcrumbs-item-label">
                        {{ $label }}
                    </span>
                @else
                    <a
                        {{ \Filament\Support\generate_href_html($url) }}
                        class="fi-breadcrumbs-item-label"
                    >
                        {{ $label }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
