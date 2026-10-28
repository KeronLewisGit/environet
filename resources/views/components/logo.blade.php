@props(['tone' => 'dark', 'small' => false, 'mark' => false])
{{--
    Vector redraw of the firm's own mark from the environetinc.com banner (images/topbanner.jpg):
    the navy "e" ring with green flippers and head, like a honu, until the firm supplies the
    original artwork. The word mark "Environet, Inc." is set in Marcellus, the closest open face
    to the banner's flared lettering, over the banner's own tagline.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 100 100" @class(['flex-none', 'h-9 w-9' => $small, 'h-11 w-11' => ! $small]) aria-hidden="true">
        <g fill="{{ $tone === 'light' ? '#3fc35a' : '#01a02c' }}"><circle cx="86" cy="13" r="8.5"/>
        <path d="M31 7c9-6 23-7 32-2 5 3 8 7 10 12-7-4-13-6-21-5-8 1-15-1-21-5z"/>
        <path d="M0 45c5-4 12-6 19-4l3 3c-6 0-11 1-15 3-3 1-5 0-7-2z"/>
        <path d="M53 84c5 3 7 7 7 12 0 2-1 3-2 4-3-3-3-7-3-11 0-2-1-3-2-5z"/>
        <path d="M87 38c5 5 8 12 7 20-1 7-3 11-7 15 0-7 1-12 0-18-1-7-3-11-6-15z"/>
        <path d="M30 53c16 2 33 1 46-4 4-2 6-5 7-10l4 1c-1 6-5 11-11 14-13 5-29 6-46 5z"/></g>
        <path fill="{{ $tone === 'light' ? '#ffffff' : '#033491' }}" class="{{ $tone === 'light' ? '' : 'logo-ring' }}" d="M79 36C74 23 64 16 52 16 36 16 23 30 23 50s12 34 29 34c11 0 20-5 27-14l-3-2c-6 7-13 11-21 11-13 0-23-12-23-29S38 20 52 20c10 0 17 6 21 17z"/>
    </svg>
    @unless ($mark)
        <span class="leading-none">
            <span @class(['block font-brand tracking-[0.005em]', 'text-[1.3rem]' => $small, 'text-[1.6rem]' => ! $small, 'text-white' => $tone === 'light', 'logo-word' => $tone !== 'light'])>Environet, Inc.</span>
            @unless ($small)
                <span @class(['mt-1 text-[0.75rem] font-semibold tracking-[0.01em]', 'hidden min-[420px]:block' => $tone !== 'light', 'block' => $tone === 'light', 'text-white/75' => $tone === 'light', 'text-ink-2' => $tone !== 'light'])>{{ enet('tagline') }}</span>
            @endunless
        </span>
    @endunless
</span>
