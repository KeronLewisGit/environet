@php
    $types = collect(\App\Enums\VendorType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all();
    $required = collect(\App\Enums\VendorType::cases())->mapWithKeys(fn ($t) => [$t->value => array_map(fn ($k) => $k->value, $t->requiredDocuments())])->all();
    $needsUei = collect(\App\Enums\VendorType::cases())->filter(fn ($t) => $t->needsUei())->map(fn ($t) => $t->value)->values()->all();
    $setAsides = collect(\App\Enums\SetAside::declarable())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    $type = input_string(old('type')) ?: 'subcontractor';
@endphp
<x-layouts.site title="Teaming partners and vendors" description="Register with Environet, Inc. as a teaming partner, subcontractor, supplier or professional services firm. Upload your insurance and licenses once; we remind you before they expire.">
    <x-page-header title="Team with us, or join our vendor list" kicker="Teaming and vendors" tone="green" photo="crew-concrete" position="center 35%"
        lead="Register once with your company details, UEI, the work you do and your current insurance and licenses. Our procurement team checks them, and we email you a renewal link before anything expires." />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('teaming.store') }}" enctype="multipart/form-data" class="min-w-0 space-y-10" x-data="teamingForm(@js($type), @js($required), @js($needsUei))" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />

            <x-fieldset n="1" title="Your company">
                <fieldset>
                    <legend class="label">How would you work with us?</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($types as $v => $t)
                            <label class="choice"><input type="radio" name="type" value="{{ $v }}" x-model="type" @checked($type === $v) required>{{ $t }}</label>
                        @endforeach
                    </div>
                    <x-error for="type" />
                </fieldset>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="company_name" label="Legal business name" :required="true" maxlength="160" autocomplete="organization" hint="As registered in SAM.gov, if you are registered." />
                    <x-field name="trading_as" label="Doing business as (DBA)" :optional="true" maxlength="160" />
                    <div>
                        <label for="f-uei" class="label">Unique Entity ID (UEI) <span class="opt" x-show="!needsUei.includes(type)">(optional)</span></label>
                        <input id="f-uei" name="uei" value="{{ input_string(old('uei')) }}" class="input num uppercase" maxlength="14" autocapitalize="characters" spellcheck="false" :required="needsUei.includes(type)" aria-describedby="f-uei-hint" @error('uei') aria-invalid="true" @enderror>
                        <span id="f-uei-hint" class="hint">12 letters and digits, from SAM.gov. Teaming partners and subcontractors need one.</span>
                        <x-error for="uei" />
                    </div>
                    <x-field name="cage" label="CAGE code" :optional="true" maxlength="5" class="num uppercase" autocapitalize="characters" spellcheck="false" hint="5 characters" />
                    <x-field name="naics" label="Primary NAICS codes" :optional="true" maxlength="200" inputmode="numeric" hint="Separated by commas, e.g. 562910, 238910" />
                    <x-field name="year_established" label="Year established" type="number" :optional="true" min="1900" :max="now()->year" inputmode="numeric" />
                    <x-select name="employees" label="Number of employees" :optional="true" :options="\App\Models\Vendor::EMPLOYEE_BANDS" />
                </div>
                <x-field name="address" label="Street address" :required="true" maxlength="240" autocomplete="street-address" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="city" label="City" :required="true" maxlength="80" autocomplete="address-level2" />
                    <x-field name="state" label="State or territory" :required="true" maxlength="40" autocomplete="address-level1" hint="e.g. HI, GU, CO" />
                </div>
            </x-fieldset>

            <x-fieldset n="2" title="Contact person">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="contact_name" label="Name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="contact_role" label="Title" :optional="true" maxlength="120" autocomplete="organization-title" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" hint="Renewal reminders are sent here." />
                    <x-field name="phone" label="Phone" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" hint="e.g. (808) 555-0123" />
                </div>
            </x-fieldset>

            <x-fieldset n="3" title="Size status and work">
                <x-checks name="set_asides" label="Socioeconomic status you hold" :options="$setAsides" :optional="true" hint="Self-declared. We confirm it against SAM.gov and the SBA before relying on it for a bid." cols="sm:grid-cols-2" />
                <x-checks name="trades" label="Type of work" :options="enet('trades')" hint="Choose all that apply." cols="sm:grid-cols-2" />
                <x-checks name="regions_served" label="Where you work" :options="array_combine(enet('regions'), enet('regions'))" :optional="true" cols="grid-cols-1 sm:grid-cols-2" />
                <x-field name="bonding" label="Bonding capacity" :optional="true" maxlength="120" hint="Single and aggregate, if you have a surety, e.g. $2M / $5M." />
                <x-field name="experience" label="Relevant federal or comparable work, last five years" type="textarea" :optional="true" maxlength="3000" hint="Contracts, agencies or primes and approximate values." />
            </x-fieldset>

            <x-fieldset n="4" title="Insurance and certificates" intro="PDF, JPG or PNG, 5 MB each. Stored privately and seen only by our procurement team.">
                @foreach (\App\Enums\DocumentKind::cases() as $kind)
                    @php
                        $k = 'docs.'.$kind->value;
                        $held = \App\Support\HeldUploads::names('teaming', $k.'.file') !== [];
                    @endphp
                    <div class="panel space-y-4 p-5" x-data="{ kind: @js($kind->value) }">
                        <div>
                            <h3 class="font-display text-lg font-semibold">{{ $kind->label() }} <span class="font-sans text-base font-normal text-ink-3" x-text="required[type]?.includes(kind) ? '(required)' : '(optional)'">{{ in_array($kind->value, $required[$type] ?? [], true) ? '(required)' : '(optional)' }}</span></h3>
                            <p class="hint">{{ $kind->hint() }}</p>
                        </div>
                        <div>
                            <label for="doc-{{ $kind->value }}" class="label">File</label>
                            <input id="doc-{{ $kind->value }}" type="file" name="docs[{{ $kind->value }}][file]" class="input" accept=".pdf,.jpg,.jpeg,.png" @unless($held) :required="required[type]?.includes(kind)" @endunless @error($k.'.file') aria-invalid="true" @enderror>
                            <x-held-upload form="teaming" :field="$k.'.file'" />
                            <x-error :for="$k.'.file'" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="num-{{ $kind->value }}" class="label">{{ $kind->numberLabel() }} <span class="opt">(optional)</span></label>
                                <input id="num-{{ $kind->value }}" name="docs[{{ $kind->value }}][number]" value="{{ input_string(old($k.'.number')) }}" class="input" maxlength="80">
                            </div>
                            @if ($kind->expires())
                                <div>
                                    <label for="exp-{{ $kind->value }}" class="label">Expiration date</label>
                                    <input id="exp-{{ $kind->value }}" type="date" name="docs[{{ $kind->value }}][expires_on]" value="{{ input_string(old($k.'.expires_on')) }}" class="input" min="{{ today()->addDay()->format('Y-m-d') }}" :required="required[type]?.includes(kind)" @error($k.'.expires_on') aria-invalid="true" @enderror>
                                    <x-error :for="$k.'.expires_on'" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </x-fieldset>

            <x-fieldset n="5" title="Declaration">
                <x-field name="message" label="Anything else we should know" type="textarea" :optional="true" maxlength="2000" />
                <label class="choice items-start"><input type="checkbox" name="consent" value="1" class="mt-0.5" @checked(old('consent')) required><span>I confirm the information and documents are accurate and current, that the size and socioeconomic status above is our own declaration, and I agree to Environet, Inc. keeping them to assess and manage our registration (<a class="text-link" href="{{ route('privacy') }}">privacy notice</a>).</span></label>
                <x-error for="consent" />
            </x-fieldset>

            <div class="rule-top pt-6"><x-submit label="Send registration" /></div>
        </form>

        <aside class="space-y-6 lg:pt-6">
            <div class="panel p-5">
                <h2 class="font-display text-lg font-semibold">What you need</h2>
                <ul class="ticked mt-3 space-y-2 text-ink-2">
                    <li>Your UEI from SAM.gov (teaming partners and subcontractors)</li>
                    <li>Certificate of general liability insurance</li>
                    <li>Subcontractors: workers' compensation and your contractor license</li>
                    <li>The expiration date of each</li>
                </ul>
                <p class="hint mt-4">A starting list; the firm will confirm its own requirements.</p>
            </div>
            <x-sample-tag :tilt="false" title="Your registration" :foot="false" :fields="['Reference' => 'TP-yy-00000', 'Renewals' => 'Emailed link, 30 days before expiry', 'Who sees it' => 'Procurement team only']" />
        </aside>
    </section>
</x-layouts.site>
