{{-- Wraps Lingua's root .lingua scope in an optional forced .dark ancestor.
     Lingua's own CSS build uses a class-based dark variant (&:where(.dark, .dark *)),
     so it normally follows the host app's <html class="dark">. When the host has no
     class-based dark toggle (or the OS preference doesn't match), this lets Lingua's
     dark theme render regardless, via config('lingua.dark_mode.force') or the
     Settings UI toggle. --}}
@php
    use Rivalex\Lingua\Models\LinguaSetting;

    $forceDark = (bool) LinguaSetting::get(
        LinguaSetting::KEY_DARK_MODE_FORCE,
        config('lingua.dark_mode.force', false),
    );
@endphp
<div @class(['dark' => $forceDark])>
    <div class="lingua">
        {{ $slot }}
    </div>
</div>
