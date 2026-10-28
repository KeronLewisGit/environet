@props(['tone' => 'dark', 'small' => false, 'mark' => false])
{{--
    Vector redraw of the firm's own mark (the navy "e" ring with green landmasses and a green
    dot, environetinc.com banner) until the firm supplies the original artwork. Word mark set
    in Space Grotesk.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 100 100" @class(['flex-none', 'h-9 w-9' => $small, 'h-11 w-11' => ! $small]) aria-hidden="true">
        <g fill="{{ $tone === 'light' ? '#6cc644' : '#43a01b' }}"><circle cx="86" cy="13" r="8.5"/>
        <path d="M31 7c9-6 23-7 32-2 5 3 8 7 10 12-7-4-13-6-21-5-8 1-15-1-21-5z"/>
        <path d="M0 45c5-4 12-6 19-4l3 3c-6 0-11 1-15 3-3 1-5 0-7-2z"/>
        <path d="M53 84c5 3 7 7 7 12 0 2-1 3-2 4-3-3-3-7-3-11 0-2-1-3-2-5z"/>
        <path d="M87 38c5 5 8 12 7 20-1 7-3 11-7 15 0-7 1-12 0-18-1-7-3-11-6-15z"/>
        <path d="M30 53c16 2 33 1 46-4 4-2 6-5 7-10l4 1c-1 6-5 11-11 14-13 5-29 6-46 5z"/></g>
        <path fill="{{ $tone === 'light' ? '#ffffff' : '#003386' }}" class="{{ $tone === 'light' ? '' : 'logo-ring' }}" d="M79 36C74 23 64 16 52 16 36 16 23 30 23 50s12 34 29 34c11 0 20-5 27-14l-3-2c-6 7-13 11-21 11-13 0-23-12-23-29S38 20 52 20c10 0 17 6 21 17z"/>
    </svg>
    @unless ($mark)
        <span class="leading-none">
            <span @class(['block font-display font-bold tracking-tight', 'text-[1.15rem]' => $small, 'text-[1.4rem]' => ! $small, 'text-white' => $tone === 'light', 'logo-word' => $tone !== 'light'])>environet<span class="font-medium opacity-70">, inc.</span></span>
            <span @class(['mt-1 block text-[0.6875rem] font-medium uppercase tracking-[0.16em]', 'text-white/70' => $tone === 'light', 'text-ink-3' => $tone !== 'light'])>Since {{ enet('founded') }} · Hawaiʻi</span>
        </span>
    @endunless
</span>
