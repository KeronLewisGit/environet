@php
    $max = \App\Http\Requests\StoreApplicationRequest::MAX_CERTS;
    $old = array_values(array_filter((array) old('certs', []), fn ($c) => is_array($c) && input_string($c['kind'] ?? '') !== ''));
    $shown = max(3, count($old));
    $kinds = collect(\App\Enums\CertificationKind::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    $offices = collect(\App\Enums\Office::cases())->mapWithKeys(fn ($o) => [$o->value => $o->label()])->all();
@endphp
<x-layouts.site title="Apply" description="Apply to work with Environet, Inc.: field scientists, UXO technicians, construction trades and office roles in Hawaiʻi, Guam and Colorado.">
    <x-page-header :title="$selected ? 'Apply: '.$selected->title : 'Apply to work with us'" kicker="Careers" tone="orange" photo="crew-concrete" position="center 35%"
        lead="It takes about five minutes. Field and trades roles can apply without a résumé; scientist, engineering, construction management and office roles need one." />
    <section class="wrap mt-12 max-w-3xl md:mt-16">
        <form method="POST" action="{{ route('careers.store') }}" enctype="multipart/form-data" class="space-y-10" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />
            <x-fieldset n="1" title="The work">
                @if ($jobs->isNotEmpty())
                    <x-select name="job_posting_id" label="Opening" :options="$jobs->pluck('title', 'id')->all()" :value="$selected?->id" placeholder="General application (no specific opening)" />
                @endif
                <x-select name="category" label="Type of work" :required="true" :value="$selected?->category->value" :options="collect(\App\Enums\JobCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" hint="If you chose an opening, its type of work is used." />
                <x-radios name="office" label="Office you want to work from" :options="$offices" :required="true" cols="sm:grid-cols-2" />
                <label class="choice"><input type="checkbox" name="can_travel" value="1" @checked(old('can_travel'))>I can travel to project sites on other islands, Guam or the mainland</label>
                <x-select name="experience" label="Experience in this kind of work" :required="true" :options="collect(\App\Enums\Experience::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
                <x-field name="available_from" label="Earliest start date" type="date" :optional="true" :min="today()->format('Y-m-d')" />
            </x-fieldset>

            <x-fieldset n="2" title="Certifications" intro="List your current training with its expiry date. HR checks the card or certificate before anyone goes to site.">
                <div x-data="certRows({{ $shown }}, {{ $max }})" class="space-y-4">
                    @for ($i = 0; $i < $max; $i++)
                        @php($row = $old[$i] ?? [])
                        <div class="panel grid gap-4 p-4 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]" @if($i >= $shown) x-show="shown > {{ $i }}" @endif>
                            <div>
                                <label for="cert-kind-{{ $i }}" class="label">Certification {{ $i + 1 }} <span class="opt">(optional)</span></label>
                                <select id="cert-kind-{{ $i }}" name="certs[{{ $i }}][kind]" class="input" @error("certs.$i.kind") aria-invalid="true" @enderror>
                                    <option value="">Choose…</option>
                                    @foreach ($kinds as $v => $t)<option value="{{ $v }}" @selected(input_string($row['kind'] ?? '') === $v)>{{ $t }}</option>@endforeach
                                </select>
                                <x-error :for="'certs.'.$i.'.kind'" />
                            </div>
                            <div>
                                <label for="cert-exp-{{ $i }}" class="label">Expires</label>
                                <input id="cert-exp-{{ $i }}" type="date" name="certs[{{ $i }}][expires_on]" value="{{ input_string($row['expires_on'] ?? '') }}" class="input" @error("certs.$i.expires_on") aria-invalid="true" @enderror>
                                <x-error :for="'certs.'.$i.'.expires_on'" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="cert-detail-{{ $i }}" class="label">Card number, provider or name <span class="opt">(optional; required for "Other")</span></label>
                                <input id="cert-detail-{{ $i }}" name="certs[{{ $i }}][detail]" value="{{ input_string($row['detail'] ?? '') }}" class="input" maxlength="120" @error("certs.$i.detail") aria-invalid="true" @enderror>
                                <x-error :for="'certs.'.$i.'.detail'" />
                            </div>
                        </div>
                    @endfor
                    <button type="button" class="btn btn-outline btn-sm" x-show="shown < max" x-cloak @click="add()"><x-icon name="plus" class="h-4 w-4" />Add another certification</button>
                    <x-error for="certs" />
                </div>
                <x-field name="qualifications" label="Other qualifications, trades and equipment" type="textarea" :optional="true" maxlength="2000" hint="e.g. degree, PG or PE license, DDESB-approved school, trade, equipment you operate." />
            </x-fieldset>

            <x-fieldset n="3" title="About you">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="first_name" label="First name" :required="true" maxlength="80" autocomplete="given-name" />
                    <x-field name="last_name" label="Last name" :required="true" maxlength="80" autocomplete="family-name" />
                    <x-field name="phone" label="Phone" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" hint="We call or text this number." />
                    <x-field name="email" label="Email" type="email" maxlength="255" autocomplete="email" hint="Needed for scientist, management and office roles." />
                </div>
                <x-select name="region" label="Where you live now" :optional="true" :options="array_combine(enet('regions'), enet('regions'))" />
            </x-fieldset>

            <x-fieldset n="4" title="Résumé and message">
                <div>
                    <label for="f-cv" class="label">Résumé</label>
                    <input id="f-cv" type="file" name="cv" class="input" accept=".pdf,.doc,.docx" aria-describedby="f-cv-hint" @error('cv') aria-invalid="true" @enderror>
                    <span id="f-cv-hint" class="hint">PDF or Word, up to 5 MB. Optional for field and trades roles.</span>
                    <x-held-upload form="application" field="cv" />
                    <x-error for="cv" />
                </div>
                <x-field name="message" label="Anything else" type="textarea" :optional="true" maxlength="2000" />
                <label class="choice items-start"><input type="checkbox" name="consent" value="1" class="mt-0.5" @checked(old('consent')) required><span>I agree to Environet, Inc. keeping my application for up to 12 months to consider me for work, and I understand my certifications will be checked before I am placed on a site (<a class="text-link" href="{{ route('privacy') }}">privacy notice</a>).</span></label>
                <x-error for="consent" />
            </x-fieldset>
            <p class="hint">{{ enet('careers.eeo') }}</p>
            <div class="rule-top pt-6"><x-submit label="Send my application" /></div>
        </form>
    </section>
</x-layouts.site>
