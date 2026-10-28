@php($office = (array) enet('offices.oahu'))
<x-layouts.site title="Privacy notice" description="How Environet, Inc. handles the details sent through this website.">
    <x-page-header photo="honolulu" title="Privacy notice" kicker="Privacy" :tag="false" lead="What we keep when you send an RFQ, a teaming or vendor registration, a job application or a message, and why." />
    <section class="wrap prose-copy measure mt-12 md:mt-16">
        <p>This website is run by {{ enet('name') }}, {{ $office['street'] }}, {{ $office['city'] }}. It is written with United States federal law and the laws of the State of Hawaiʻi in mind, including Hawaiʻi's security breach notification law (HRS chapter 487N) and its law on disposing of personal information (HRS chapter 487R). Questions: <a href="mailto:{{ enet('email') }}">{{ enet('email') }}</a> or {{ format_phone(enet('phone')) }}.</p>
        <h2>What we collect</h2>
        <ul>
            <li><strong>RFQs and bid inquiries:</strong> the agency or company, contact person, solicitation details and the documents you upload.</li>
            <li><strong>Teaming and vendor registration:</strong> company details, UEI and CAGE code, the size and socioeconomic status you declare, a contact person, the work you do, and the insurance certificates and licenses you upload with their numbers and expiration dates.</li>
            <li><strong>Job applications:</strong> your name, phone, email, preferred office, experience, the certifications you list with their expiry dates, and a résumé if you attach one.</li>
            <li><strong>Messages:</strong> your name, contact details and message.</li>
        </ul>
        <p>We do not ask for Social Security numbers, driver's license numbers or bank details through this website. Please do not include them in uploads or messages.</p>
        <h2>Why, and who sees it</h2>
        <p>We use these details only to respond: to decide whether to bid, to assess and manage teaming partners, subcontractors and suppliers, to consider applicants for work, and to reply to messages. Each kind of submission is seen only by the staff who handle it (business development, procurement or human resources) and the site administrator. We do not sell your personal information or share it for advertising.</p>
        <h2>Where it is kept</h2>
        <p>Uploads are stored on a private part of our web server that is not reachable from the internet; staff open them after signing in, and each opening is logged. Documents we share with you go out through private links that expire. Emails confirming your reference number are sent from our website.</p>
        <h2>How long</h2>
        <ul>
            <li>RFQ and bid records: for the life of the solicitation and any resulting contract, plus the period federal contract record rules require.</li>
            <li>Vendor records: while you remain on our approved list. We email a renewal link before a certificate or policy expires.</li>
            <li>Job applications: up to 12 months, unless you ask us to delete yours sooner.</li>
        </ul>
        <p>When we no longer need records, we destroy them so the personal information cannot be read or reconstructed.</p>
        <h2>If something goes wrong</h2>
        <p>If personal information we hold is accessed without authorization, we will notify the people affected without unreasonable delay, as Hawaiʻi law requires.</p>
        <h2>Your choices</h2>
        <p>You can ask to see, correct or delete the details we hold about you by emailing <a href="mailto:{{ enet('email') }}">{{ enet('email') }}</a>. Quote your reference number if you have one. Residents of states with their own privacy laws can use the same address to exercise those rights.</p>
        <h2>Children</h2>
        <p>This website is for businesses, agencies and job applicants. It is not directed at children under 13, and we do not knowingly collect their information.</p>
        <h2>Cookies and tracking</h2>
        <p>The site uses one session cookie to keep forms secure and a setting that remembers light or dark mode. There is no advertising or cross-site tracking, so browser "Do Not Track" signals do not change how the site works.</p>
        <p class="hint">Last updated {{ now()->format('F Y') }}. To be reviewed by the firm and its counsel before launch.</p>
    </section>
</x-layouts.site>
