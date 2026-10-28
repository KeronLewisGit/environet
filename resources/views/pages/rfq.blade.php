<x-layouts.site title="RFQs and bid inquiries" description="Contracting officers and prime contractors: send Environet, Inc. an RFQ, RFP, IFB, sources sought, task order request or teaming request. You get a reference number by email.">
    <x-page-header title="Send an RFQ or bid inquiry" kicker="RFQs and bid inquiries" photo="kaneohe"
        lead="Contracting offices, prime contractors and public agencies: send the request straight to our business development team. You get a reference number on screen and by email." />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('solicitations.store') }}" enctype="multipart/form-data" class="min-w-0 space-y-10" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />

            <x-fieldset n="1" title="Who is asking">
                <x-radios name="source" label="You are a" :options="\App\Models\Solicitation::SOURCES" :required="true" cols="sm:grid-cols-2" />
                <x-field name="organization" label="Contracting office, agency or company" :required="true" maxlength="160" autocomplete="organization" hint="For example a USACE district, a NAVFAC office, an Air Force contracting squadron or a prime contractor." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="contact_name" label="Contact name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="contact_role" label="Position" :optional="true" maxlength="120" autocomplete="organization-title" hint="e.g. Contract Specialist" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" hint="We email your reference number and our bid decision here." />
                    <x-field name="phone" label="Phone" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" hint="e.g. (808) 555-0123" />
                </div>
            </x-fieldset>

            <x-fieldset n="2" title="The requirement">
                <x-field name="title" label="Project or solicitation title" :required="true" maxlength="200" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-select name="type" label="Type of request" :required="true" :options="collect(\App\Enums\SolicitationType::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all()" />
                    <x-field name="solicitation_number" label="Solicitation number" :optional="true" maxlength="80" hint="As on SAM.gov or the request, e.g. W9128A26Q0001." />
                    <x-select name="set_aside" label="Set-aside" :optional="true" :options="collect(\App\Enums\SetAside::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                    <x-field name="naics" label="NAICS code" :optional="true" maxlength="6" inputmode="numeric" hint="6 digits, e.g. 236220" />
                    <x-select name="region" label="Place of performance" :optional="true" :options="array_combine(enet('regions'), enet('regions'))" />
                    <x-field name="location" label="Installation or site" :optional="true" maxlength="160" hint="e.g. Schofield Barracks" />
                </div>
                <x-field name="scope" label="Scope of work" type="textarea" :required="true" maxlength="5000" rows="6" hint="A short description is enough; attach the documents below." />
                <x-field name="estimated_value" label="Estimated magnitude" :optional="true" maxlength="80" hint="Only if it is published, e.g. $1M to $5M." />
            </x-fieldset>

            <x-fieldset n="3" title="Key dates">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="due_date" label="Responses due: date" type="date" :required="true" :min="today()->format('Y-m-d')" />
                    <x-field name="due_time" label="Responses due: time" type="time" :required="true" value="14:00" hint="Hawaiʻi time (HST)" />
                    <x-field name="questions_due_on" label="Questions due" type="date" :optional="true" />
                    <x-field name="site_visit_date" label="Site visit or pre-proposal conference" type="date" :optional="true" />
                    <x-field name="site_visit_time" label="Site visit time" type="time" :optional="true" />
                </div>
                <label class="choice"><input type="checkbox" name="bond_required" value="1" @checked(old('bond_required'))>A bid guarantee, or performance and payment bonds, are required</label>
            </x-fieldset>

            <x-fieldset n="4" title="Documents" intro="Attach the solicitation, statement of work and drawings, or paste a link to them (for example on SAM.gov or PIEE). Up to {{ \App\Models\Solicitation::MAX_FILES }} files, 10 MB each.">
                <div>
                    <label for="f-files" class="label">Documents <span class="opt">(optional)</span></label>
                    <input id="f-files" type="file" name="files[]" multiple class="input" accept=".{{ implode(',.', \App\Models\Solicitation::FILE_TYPES) }}" aria-describedby="f-files-hint">
                    <span id="f-files-hint" class="hint">PDF, Word, Excel or ZIP. Stored privately for our business development team.</span>
                    <x-held-upload form="solicitation" field="files" />
                    <x-error for="files" /><x-error for="files.0" /><x-error for="files.1" /><x-error for="files.2" /><x-error for="files.3" /><x-error for="files.4" />
                </div>
                <x-field name="documents_url" label="Link to the documents" type="url" :optional="true" maxlength="300" placeholder="https://" />
                <x-field name="message" label="Anything else" type="textarea" :optional="true" maxlength="3000" />
            </x-fieldset>

            <div class="rule-top pt-6">
                <x-submit label="Send the request" />
                <p class="hint mt-3">By sending, you agree that we keep these details to respond to your request. See our <a class="text-link" href="{{ route('privacy') }}">privacy notice</a>.</p>
            </div>
        </form>

        <aside class="space-y-6 lg:pt-6">
            <x-sample-tag :tilt="false" title="For your market research" />
            <div class="panel p-5">
                <h2 class="font-display text-lg font-semibold">What happens next</h2>
                <ul class="ticked mt-3 space-y-2 text-ink-2">
                    <li>A reference number (RQ-yy-00000) on screen and by email</li>
                    <li>A named contact in business development</li>
                    <li>A bid or no-bid reply before your response date</li>
                </ul>
            </div>
            <div class="panel p-5">
                <h2 class="font-display text-lg font-semibold">Capability statement</h2>
                <p class="mt-2 text-ink-2">Identifiers, NAICS codes, SBA certifications and past performance on one page.</p>
                <a href="{{ route('capability') }}" class="btn btn-outline mt-4 w-full"><x-icon name="file" class="h-4 w-4" />Open it</a>
            </div>
            <div class="panel p-5">
                <h2 class="font-display text-lg font-semibold">Prefer to call?</h2>
                <a href="{{ tel_href(enet('phone')) }}" class="mt-2 inline-flex min-h-11 items-center gap-2 font-semibold text-link"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(enet('phone')) }}</a>
                <a href="mailto:{{ enet('email') }}" class="flex min-h-11 items-center gap-2 font-semibold text-link [overflow-wrap:anywhere]"><x-icon name="mail" class="h-4 w-4 flex-none" />{{ enet('email') }}</a>
            </div>
        </aside>
    </section>
</x-layouts.site>
