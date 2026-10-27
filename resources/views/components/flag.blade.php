@props([
    'code' => '',
    'circle' => false,
])
{!! \Rivalex\Lingua\Support\FlagSvg::html((string) $code, (bool) $circle, (string) $attributes->get('class', '')) !!}
