@php($offices = (array) enet('offices'))
<x-layouts.site title="Contact" description="Contact Environet, Inc.: Honolulu, Kamuela, Guam and Colorado offices, telephone, email and an inquiry form.">
    <x-page-header title="Contact an office" kicker="Contact" photo="honolulu"
        lead="For RFQs and bid inquiries, use the RFQ form so the documents reach our business development team. For anything else, call, email or send a message below." />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        <div class="min-w-0 space-y-8">
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <p class="kicker text-ink-3">Telephone</p>
                    <a href="{{ tel_href(enet('phone')) }}" class="mt-1 inline-flex min-h-11 items-center font-display text-2xl font-semibold text-link">{{ format_phone(enet('phone')) }}</a>
                    <p class="hint">Honolulu office. Fax {{ format_phone(enet('fax')) }}. Hawaiʻi time ({{ enet('timezone_label') }}).</p>
                </div>
                <div>
                    <p class="kicker text-ink-3">Email</p>
                    <a href="mailto:{{ enet('email') }}" class="mt-1 inline-flex min-h-11 items-center text-lg font-semibold text-link [overflow-wrap:anywhere]">{{ enet('email') }}</a>
                    <p class="hint">Job applications: <a class="text-link" href="{{ route('careers.index') }}">careers page</a> or {{ enet('hr_email') }}.</p>
                </div>
            </div>

            <ul class="space-y-4" aria-label="Offices">
                @foreach ($offices as $key => $o)
                    <li id="office-{{ $key }}" class="card scroll-mt-28 grid overflow-hidden sm:grid-cols-[10rem_minmax(0,1fr)]">
                        <div class="relative aspect-[16/9] sm:aspect-auto">
                            <x-photo :key="$o['photo']" :alt="photo_alt($o['photo'])" sizes="(min-width: 640px) 160px, 100vw" class="h-full w-full" />
                            <span class="photo-note !text-[0.6875rem]">Illustrative</span>
                        </div>
                        <div class="p-5">
                            <p class="kicker text-ink-3">{{ $o['label'] }}</p>
                            <h2 class="mt-1 font-display text-xl font-semibold">{{ $o['name'] }}</h2>
                            <p class="mt-1 text-ink-2">@if($o['street']){{ $o['street'] }}<br>@endif{{ $o['city'] }}</p>
                            <div class="mt-1 flex flex-wrap gap-x-5">
                                @if ($o['phone'])<a href="{{ tel_href($o['phone']) }}" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-link"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($o['phone']) }}</a>@endif
                                @if ($o['map'])<a href="{{ $o['map'] }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">Map<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens Google Maps)</span></a>@endif
                            </div>
                            @if ($o['note'])<p class="hint mt-1">{{ $o['note'] }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <form method="POST" action="{{ route('contact.store') }}" class="min-w-0 space-y-8" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />
            <x-fieldset n="1" title="Send a message">
                <x-radios name="topic" label="What is it about?" :options="collect(\App\Enums\EnquiryTopic::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :required="true" cols="sm:grid-cols-2" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Your name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="organization" label="Organization" :optional="true" maxlength="160" autocomplete="organization" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" />
                    <x-field name="phone" label="Phone" type="tel" :optional="true" maxlength="30" autocomplete="tel" inputmode="tel" />
                </div>
                <x-field name="message" label="Message" type="textarea" :required="true" maxlength="3000" rows="6" />
            </x-fieldset>
            <div class="rule-top pt-6">
                <x-submit label="Send message" />
                <p class="hint mt-3">We keep these details only to reply. <a class="text-link" href="{{ route('privacy') }}">Privacy notice</a>.</p>
            </div>
            <div class="panel p-5">
                <p class="font-semibold">Sending an RFQ, IFB or sources sought?</p>
                <p class="mt-1 text-ink-2">The RFQ form takes the documents and deadlines, and gives you a reference number.</p>
                <a href="{{ route('solicitations.create') }}" class="btn btn-primary mt-3">Send an RFQ</a>
            </div>
        </form>
    </section>
</x-layouts.site>
