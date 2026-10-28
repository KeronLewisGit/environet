<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\CertificationKind;
use App\Enums\DocumentKind;
use App\Enums\EmploymentType;
use App\Enums\EnquiryStatus;
use App\Enums\EnquiryTopic;
use App\Enums\Experience;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Enums\Office;
use App\Enums\Role;
use App\Enums\RoomCategory;
use App\Enums\SetAside;
use App\Enums\SolicitationStatus;
use App\Enums\SolicitationType;
use App\Enums\VendorStatus;
use App\Enums\VendorType;
use App\Models\DocumentRoom;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Solicitation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for the pitch: every organization, person, solicitation and vendor here is fictional
 * and labelled "(sample)" or "Sample"; UEIs start SMPL. Logins: admin|bd|procurement|hr@environet.test / password.
 * Never run in production.
 */
class DemoSeeder extends Seeder
{
    private const array FIRST = ['Kai', 'Leilani', 'Mark', 'Jasmine', 'Noa', 'Kristen', 'Daniel', 'Malia', 'Ryan', 'Alana', 'Jesse', 'Mele', 'Travis', 'Kehau', 'Joseph', 'Ana', 'Brandon', 'Lani', 'Miguel', 'Tiana'];

    private const array LAST = ['Kahananui', 'Santos', 'Nakamura', 'Reyes', 'Kealoha', 'Tanaka', 'Smith', 'Cruz', 'Akana', 'Fernandez', 'Yamamoto', 'Johnson', 'Pascual', 'Kim', 'Medeiros', 'Taitano', 'Garcia', 'Lee'];

    public function run(): void
    {
        // Fictional data and "password" logins: never on the live site. A pitch preview runs
        // APP_ENV=production with ENET_PREVIEW=true, so it is allowed there.
        if (app()->isProduction() && ! enet('preview')) {
            $this->command?->error('DemoSeeder does not run in production unless ENET_PREVIEW=true (a pitch preview).');

            return;
        }
        mt_srand(808);
        $staff = $this->users();
        $this->solicitations($staff);
        $this->vendors($staff['procurement']);
        $this->careers();
        $this->enquiries();
        $this->rooms($staff['bd']);
    }

    /** @return array<string, User> */
    private function users(): array
    {
        $out = [];
        foreach ([
            'admin' => ['Demo Administrator', Role::Admin],
            'bd' => ['Demo Business Developer', Role::Bd],
            'procurement' => ['Demo Procurement Officer', Role::Procurement],
            'hr' => ['Demo HR Officer', Role::Hr],
        ] as $key => [$name, $role]) {
            $out[$key] = User::updateOrCreate(['email' => $key.'@environet.test'], ['name' => $name, 'password' => Hash::make('password'), 'role' => $role, 'active' => true]);
        }
        $out['bd2'] = User::updateOrCreate(['email' => 'proposals@environet.test'], ['name' => 'Demo Proposal Manager', 'password' => Hash::make('password'), 'role' => Role::Bd, 'active' => true]);

        return $out;
    }

    private function name(): string
    {
        return self::FIRST[mt_rand(0, count(self::FIRST) - 1)].' '.self::LAST[mt_rand(0, count(self::LAST) - 1)];
    }

    private function phone(): string
    {
        // 555-01xx numbers are reserved for fiction.
        return '808-555-01'.str_pad((string) mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
    }

    /** A tiny valid PDF on the private disk, so "open" works in the demo. */
    private function pdf(string $path, string $title): void
    {
        $text = str_replace(['(', ')', '\\'], '', 'SAMPLE DOCUMENT - '.$title);
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        Storage::disk('local')->put($path, $pdf);
    }

    /**
     * Creates a record as if it arrived at $time, with its "received" timeline note dated the same.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  callable(): T  $create
     * @return T
     */
    private function at(Carbon $time, callable $create): mixed
    {
        Carbon::setTestNow($time);
        try {
            $record = $create();
            if (method_exists($record, 'log')) {
                $record->log('Received from the website.');
            }

            return $record;
        } finally {
            Carbon::setTestNow();
        }
    }

    /** A made-up UEI that cannot be mistaken for a real one: SMPL + 8 characters. */
    private function uei(): string
    {
        return 'SMPL'.strtoupper(Str::random(8));
    }

    /** @param array<string, User> $staff */
    private function solicitations(array $staff): void
    {
        foreach (self::solicitationRows() as $i => [$title, $org, $source, $type, $setAside, $naics, $region, $days, $status, $who]) {
            $due = today()->addDays($days)->setTime([10, 12, 14][$i % 3], 0);
            $received = $days > 0 ? now()->subDays(mt_rand(2, 18))->subHours(mt_rand(0, 8)) : $due->copy()->subDays(mt_rand(20, 35));
            $solicitation = $this->at($received, fn () => Solicitation::create([
                'status' => SolicitationStatus::New, 'source' => $source, 'organization' => $org, 'contact_name' => $this->name(),
                'contact_role' => $source === 'prime' ? 'Estimator' : 'Contract Specialist',
                'email' => 'contracting'.($i + 1).'@example.org', 'phone' => $this->phone(), 'title' => $title,
                'solicitation_number' => $source === 'federal' ? 'SAMPLE-26-'.strtoupper(substr($type->value, 0, 1)).'-'.str_pad((string) (10 + $i), 4, '0', STR_PAD_LEFT) : null,
                'type' => $type, 'set_aside' => $setAside, 'naics' => $naics, 'region' => $region, 'location' => null,
                'scope' => "Sample scope for the demo. Furnish all labor, equipment and materials for the work described in the statement of work.\nPeriod of performance: ".mt_rand(90, 540).' calendar days.',
                'estimated_value' => $source === 'federal' ? ['$250K to $1M', '$1M to $5M', '$5M to $10M'][$i % 3] : null,
                'response_due_at' => $due, 'site_visit_at' => $days > 7 ? $due->copy()->subDays(7)->setTime(9, 0) : null,
                'questions_due_on' => $days > 10 ? $due->copy()->subDays(10) : null, 'bond_required' => $source === 'federal' && $i % 2 === 0,
                'documents_url' => $source === 'federal' && $i % 3 === 0 ? 'https://sam.gov/' : null, 'source_url' => url('/rfq'),
            ]));
            $this->pdf("solicitations/{$solicitation->id}/solicitation.pdf", $title);
            $solicitation->files()->create(['path' => "solicitations/{$solicitation->id}/solicitation.pdf", 'original_name' => 'Solicitation and SOW.pdf', 'mime' => 'application/pdf', 'size' => 1400]);
            if ($who) {
                $solicitation->assigned_to = $staff[$who]->id;
                $solicitation->log("Assigned to {$staff[$who]->name}.", 'assign', $staff['admin']);
            }
            if ($status !== SolicitationStatus::New) {
                $solicitation->moveTo($status);
                $solicitation->status_changed_at = $days < 0 ? $due->copy()->addDays(mt_rand(1, 20)) : now()->subDays(1);
                $solicitation->log('Status changed to '.$status->label().'.', 'status', $staff[$who ?? 'bd']);
            }
            $solicitation->save();
        }
        $first = Solicitation::orderBy('response_due_at')->where('status', SolicitationStatus::Pricing)->first();
        $first?->log('Site visit done. Quotes requested from a drilling subcontractor and the analytical lab; UXO escort needed for the access road.', 'note', $staff['bd']);
    }

    /** @return list<array{string, string, string, SolicitationType, SetAside|null, string|null, string, int, SolicitationStatus, string|null}> */
    private static function solicitationRows(): array
    {
        return [
            // title, organization, source, type, set-aside, NAICS, place, due in days, status, assignee
            ['Repair barracks fire sprinkler system (sample)', 'Sample Engineer District', 'federal', SolicitationType::TaskOrder, SetAside::SmallBusiness, '236220', 'Oʻahu', 2, SolicitationStatus::Pricing, 'bd'],
            ['Groundwater monitoring, former fuel site, year 3 (sample)', 'Sample Naval Facilities Office', 'federal', SolicitationType::Rfq, SetAside::Hubzone, '562910', 'Oʻahu', 4, SolicitationStatus::Reviewing, 'bd2'],
            ['MEC construction support, range road upgrade (sample)', 'Sample Prime Contractor LLC', 'prime', SolicitationType::SubQuote, null, '562910', 'Hawaiʻi Island', 6, SolicitationStatus::New, null],
            ['Hangar door replacement (sample)', 'Sample Contracting Squadron', 'federal', SolicitationType::Rfp, SetAside::Hubzone, '236220', 'Guam', 6, SolicitationStatus::Pricing, 'bd'],
            ['Phase II environmental site assessment, county yard (sample)', 'Sample County Department', 'state', SolicitationType::Rfq, null, '541620', 'Maui', 13, SolicitationStatus::Reviewing, 'bd2'],
            ['Sources sought: munitions response, former training area (sample)', 'Sample Engineer District', 'federal', SolicitationType::SourcesSought, null, '562910', 'Hawaiʻi Island', 19, SolicitationStatus::New, null],
            ['UST removal and closure, two tanks (sample)', 'Sample State Agency', 'state', SolicitationType::Ifb, null, '562910', 'Kauaʻi', 26, SolicitationStatus::New, null],
            ['Teaming request: MATOC recompete (sample)', 'Sample Joint Venture LLC', 'prime', SolicitationType::Teaming, SetAside::Vosb, '236220', 'Oʻahu', 33, SolicitationStatus::Reviewing, 'bd'],
            ['Waterline replacement, family housing (sample)', 'Sample Naval Facilities Office', 'federal', SolicitationType::Ifb, SetAside::SmallBusiness, '237110', 'Oʻahu', 41, SolicitationStatus::New, null],
            ['Landfill gas monitoring (sample)', 'Sample Contracting Squadron', 'federal', SolicitationType::Rfq, SetAside::SmallBusiness, '562910', 'Guam', -6, SolicitationStatus::Submitted, 'bd'],
            ['Chiller replacement, administration building (sample)', 'Sample Engineer District', 'federal', SolicitationType::TaskOrder, SetAside::Hubzone, '238220', 'Oʻahu', -40, SolicitationStatus::Won, 'bd2'],
            ['Asbestos survey, school campus (sample)', 'Sample State Agency', 'state', SolicitationType::Rfq, null, '541620', 'Oʻahu', -55, SolicitationStatus::Lost, 'bd'],
            ['Warehouse slab and site work (sample)', 'Sample Developer Inc.', 'private', SolicitationType::Rfp, null, '236220', 'Oʻahu', -20, SolicitationStatus::Declined, 'bd2'],
            ['Geophysical survey support (sample)', 'Sample Prime Contractor LLC', 'prime', SolicitationType::SubQuote, null, '541360', 'Hawaiʻi Island', -90, SolicitationStatus::Won, 'bd'],
        ];
    }

    private function vendors(User $officer): void
    {
        $rows = [
            // company, type, work, self-declared status, vendor status, days to earliest expiry, state
            ['Kīpuka Drilling LLC (sample)', VendorType::Subcontractor, ['drilling'], [SetAside::SmallBusiness], VendorStatus::Approved, 9, 'HI'],
            ['Lava Rock Geophysics Inc. (sample)', VendorType::Teaming, ['geophysics', 'mec'], [SetAside::SmallBusiness, SetAside::Sdvosb], VendorStatus::Approved, -4, 'HI'],
            ['Kalo Analytical Lab (sample)', VendorType::Professional, ['laboratory'], [SetAside::SmallBusiness], VendorStatus::Approved, 22, 'HI'],
            ['ʻEwa Plain Waste Haulers (sample)', VendorType::Subcontractor, ['waste'], [SetAside::SmallBusiness, SetAside::Wosb], VendorStatus::Approved, 150, 'HI'],
            ['Lagu Equipment Rental (sample)', VendorType::Equipment, ['equipment'], [], VendorStatus::Approved, 27, 'GU'],
            ['Puʻu Concrete Works (sample)', VendorType::Subcontractor, ['concrete'], [SetAside::Hubzone], VendorStatus::Approved, 210, 'HI'],
            ['Ahupuaʻa Cultural Monitoring (sample)', VendorType::Professional, ['archaeology'], [SetAside::SmallBusiness, SetAside::Wosb], VendorStatus::Approved, 95, 'HI'],
            ['Mesa Abatement Co. (sample)', VendorType::Subcontractor, ['abatement'], [SetAside::SmallBusiness], VendorStatus::Reviewing, 180, 'CO'],
            ['Kukui Electrical Contractors (sample)', VendorType::Subcontractor, ['electrical'], [SetAside::EightA], VendorStatus::New, 240, 'HI'],
            ['Kai Harbor Supply (sample)', VendorType::Supplier, ['supply'], [], VendorStatus::New, 120, 'HI'],
            ['Latte Stone Fire Protection (sample)', VendorType::Subcontractor, ['fire'], [SetAside::SmallBusiness], VendorStatus::Approved, 300, 'GU'],
            ['Ala Traffic Control (sample)', VendorType::Subcontractor, ['traffic'], [], VendorStatus::Declined, 60, 'HI'],
        ];
        $cities = ['HI' => 'Honolulu', 'GU' => 'Tamuning', 'CO' => 'Denver'];
        $regions = (array) enet('regions');
        foreach ($rows as $i => [$company, $type, $trades, $setAsides, $status, $days, $state]) {
            $today = today();
            $received = now()->subDays(mt_rand(20, 300));
            $vendor = $this->at($received, fn () => Vendor::create([
                'status' => VendorStatus::New, 'type' => $type, 'company_name' => $company,
                'uei' => $type->needsUei() || $i % 2 === 0 ? $this->uei() : null, 'cage' => $type === VendorType::Teaming ? 'S'.strtoupper(Str::random(4)) : null,
                'naics' => $type === VendorType::Supplier ? null : ['562910', '541360', '541380', '562112', '532412', '238110', '541720', '562910', '238210', '423390', '238220', '561990'][$i],
                'set_asides' => array_map(fn (SetAside $s) => $s->value, $setAsides),
                'year_established' => mt_rand(1985, 2020), 'address' => mt_rand(100, 990).' Sample Street', 'city' => $cities[$state], 'state' => $state,
                'contact_name' => $this->name(), 'contact_role' => 'Owner', 'email' => 'vendor'.($i + 1).'@example.com', 'phone' => $this->phone(),
                'trades' => $trades, 'regions_served' => array_slice($regions, 0, mt_rand(2, 6)), 'employees' => array_keys(Vendor::EMPLOYEE_BANDS)[mt_rand(0, 3)],
                'bonding' => $type === VendorType::Subcontractor ? 'Sample: $'.mt_rand(1, 5).'M single / $'.mt_rand(6, 12).'M aggregate' : null,
                'experience' => 'Sample entry for the demo: recent subcontracts on federal projects in Hawaiʻi.', 'consented_at' => now(),
            ]));
            // Uploads and the decision carry the dates they would really have.
            Carbon::setTestNow($received);
            $kinds = $type->requiredDocuments();
            if ($setAsides !== [] && $setAsides !== [SetAside::SmallBusiness]) {
                $kinds[] = DocumentKind::SbaCert;
            }
            foreach ($kinds as $k => $kind) {
                $path = "vendors/{$vendor->id}/{$kind->value}.pdf";
                $this->pdf($path, $kind->label().' - '.$company);
                $vendor->documents()->create([
                    'kind' => $kind, 'path' => $path, 'original_name' => $kind->short().'.pdf', 'mime' => 'application/pdf', 'size' => 1400,
                    'number' => match ($kind) {
                        DocumentKind::Liability, DocumentKind::WorkersComp => 'Sample Insurance Co. / GL-'.mt_rand(10000, 99999),
                        DocumentKind::License => 'Sample license C-'.mt_rand(10000, 99999).' ('.$state.')',
                        default => 'Sample',
                    },
                    'expires_on' => $today->copy()->addDays($days + $k * 47),
                    'verified_at' => $status === VendorStatus::Approved ? $received->copy()->addDays(2) : null,
                    'verified_by' => $status === VendorStatus::Approved ? $officer->id : null,
                ]);
            }
            if ($status !== VendorStatus::New) {
                Carbon::setTestNow($received->copy()->addDays(3)->setTime(10, 15));
                $vendor->moveTo($status);
                $vendor->reviewed_by = $officer->id;
                $vendor->save();
                $vendor->log('Status changed to '.$status->label().'.', 'status', $officer);
            }
            Carbon::setTestNow();
        }
        // One renewal already in: the superseded copy stays on file.
        $renewed = Vendor::where('company_name', 'like', 'ʻEwa Plain Waste%')->first();
        if ($renewed) {
            /** @var VendorDocument $policy */
            $policy = $renewed->documents()->where('kind', DocumentKind::Liability)->firstOrFail();
            $old = $policy->replicate(['expires_on', 'superseded_at']);
            $old->fill(['expires_on' => today()->subDays(30), 'superseded_at' => now()->subDays(35)])->save();
            $renewed->log('Vendor uploaded 1 renewed certificate through the renewal link.');
        }
    }

    private function careers(): void
    {
        // Modelled on the kinds of roles the firm has advertised; every posting is a sample.
        $jobs = [
            ['UXO Technician II (sample)', JobCategory::Uxo, EmploymentType::Project, 'Hawaiʻi Island', 4, 'Surface and subsurface MEC clearance with a field team on a munitions response project.', "Operate geophysical instruments and hand-held detectors\nInvestigate anomalies under the direction of a UXO Technician III\nKeep daily field records", "Graduate of a DDESB-approved UXO school (TP 18)\nCurrent HAZWOPER 40-hour with 8-hour refresher\nAble to work outdoors in rough terrain"],
            ['UXO Technician I (sample)', JobCategory::Uxo, EmploymentType::Project, 'Hawaiʻi Island and Oʻahu', 6, 'Entry-level UXO technician for MEC field teams.', "Assist with surface sweeps and intrusive investigations\nSupport site safety and daily reporting", "Graduate of a DDESB-approved UXO school (TP 18)\nCurrent HAZWOPER 40-hour"],
            ['Environmental scientist (sample)', JobCategory::Scientist, EmploymentType::FullTime, 'Honolulu', 1, 'Field sampling, data review and reporting on site investigations and remediation projects.', "Soil, groundwater and air sampling\nPhase I and II environmental site assessments\nDraft reports and work plans", "Degree in environmental science, geology or engineering\nHAZWOPER 40-hour, or able to complete it\nValid driver's license"],
            ['Construction quality control manager (sample)', JobCategory::Construction, EmploymentType::Project, 'Guam', 1, 'Run the three-phase quality control system on a federal construction project.', "Preparatory, initial and follow-up inspections\nDaily QC reports and submittal review", "USACE CQM for Contractors certificate\n5 years of construction QC on federal projects"],
            ['Carpenters and laborers (sample)', JobCategory::Trades, EmploymentType::Project, 'Oʻahu', 5, 'Construction trades for repair and renovation projects on Oʻahu installations.', "Formwork, framing and finish carpentry\nSite cleanup and material handling", "Trade experience on commercial sites\nOSHA 10-hour construction\nAble to pass a base access check"],
            ['Site safety and health officer (sample)', JobCategory::Safety, EmploymentType::Project, 'Oʻahu', 1, 'Oversee the accident prevention plan on a construction site.', "Daily safety inspections and toolbox talks\nActivity hazard analyses", "OSHA 30-hour construction\nUSACE EM 385-1-1 training\nFirst aid and CPR"],
        ];
        $posts = [];
        foreach ($jobs as [$title, $cat, $type, $loc, $openings, $summary, $duties, $req]) {
            $posts[] = JobPosting::create(['slug' => Str::slug(str_replace(' (sample)', '', $title)), 'title' => $title, 'category' => $cat, 'employment_type' => $type, 'status' => JobStatus::Open,
                'location' => $loc, 'openings' => $openings, 'summary' => $summary, 'duties' => $duties, 'requirements' => $req, 'closes_on' => today()->addDays(mt_rand(14, 45)), 'is_demo' => true]);
        }
        JobPosting::create(['slug' => 'project-geologist-closed', 'title' => 'Project geologist (sample, closed)', 'category' => JobCategory::Scientist, 'employment_type' => EmploymentType::FullTime,
            'status' => JobStatus::Closed, 'location' => 'Honolulu', 'openings' => 1, 'summary' => 'Closed sample posting.', 'is_demo' => true]);

        $statuses = [ApplicationStatus::New, ApplicationStatus::New, ApplicationStatus::New, ApplicationStatus::Reviewing, ApplicationStatus::Interview, ApplicationStatus::Hired, ApplicationStatus::Declined];
        $offices = [Office::HawaiiIsland, Office::Oahu, Office::Oahu, Office::Guam, Office::Any, Office::Colorado];
        $regions = (array) enet('regions');
        $certsFor = [
            JobCategory::Uxo->value => [[CertificationKind::Hazwoper40, 300], [CertificationKind::Hazwoper8, 300], [CertificationKind::UxoTech2, 300], [CertificationKind::FirstAid, 500]],
            JobCategory::Scientist->value => [[CertificationKind::Hazwoper40, 200], [CertificationKind::Hazwoper8, 200]],
            JobCategory::Construction->value => [[CertificationKind::Cqm, 900], [CertificationKind::Osha30, null], [CertificationKind::Em385, null]],
            JobCategory::Trades->value => [[CertificationKind::Osha10, null]],
            JobCategory::Safety->value => [[CertificationKind::Osha30, null], [CertificationKind::Em385, null], [CertificationKind::FirstAid, 400]],
            JobCategory::Office->value => [],
        ];
        for ($i = 0; $i < 22; $i++) {
            $job = $i % 6 === 5 ? null : $posts[$i % count($posts)];
            $cat = $job->category ?? JobCategory::Trades;
            [$first, $last] = explode(' ', $this->name());
            $status = $statuses[$i % count($statuses)];
            $app = $this->at(now()->subDays(mt_rand(0, 50))->subHours(mt_rand(0, 10)), fn () => JobApplication::create([
                'job_posting_id' => $job?->id, 'category' => $cat, 'status' => ApplicationStatus::New, 'first_name' => $first, 'last_name' => $last,
                'phone' => $this->phone(), 'email' => $cat->needsCv() || $i % 2 ? strtolower($first.'.'.$last.$i).'@example.com' : null,
                'office' => $offices[$i % count($offices)], 'can_travel' => $i % 3 !== 0,
                'region' => $regions[$i % 4], 'experience' => Experience::cases()[mt_rand(1, 4)],
                'qualifications' => $cat->isField() ? 'Sample: field experience on federal projects in Hawaiʻi.' : 'Sample: BS degree, 4 years in a consulting office.',
                'available_from' => today()->addDays(mt_rand(3, 30)), 'consented_at' => now(),
            ]));
            foreach ($certsFor[$cat->value] as $k => [$kind, $span]) {
                // A few lapse soon or have lapsed, so the HR tile and chips show something.
                $expires = $span === null ? null : today()->addDays(match (true) {
                    $i % 7 === 0 && $k === 0 => -12, $i % 5 === 1 && $k === 0 => 18, default => mt_rand(60, $span)
                });
                $app->certifications()->create(['kind' => $kind, 'detail' => $kind === CertificationKind::UxoTech2 ? 'Sample UXO school, class of 20'.mt_rand(10, 22) : null, 'expires_on' => $expires,
                    'checked_at' => in_array($status, [ApplicationStatus::Interview, ApplicationStatus::Hired], true) ? now()->subDays(2) : null]);
            }
            if ($cat->needsCv() || $i % 3 === 0) {
                $path = 'cvs/demo-'.$app->id.'.pdf';
                $this->pdf($path, 'Resume - '.$app->full_name);
                $app->forceFill(['cv_path' => $path, 'cv_name' => 'Resume.pdf', 'cv_mime' => 'application/pdf', 'cv_size' => 1400])->save();
            }
            if ($status !== ApplicationStatus::New) {
                $app->moveTo($status);
                $app->save();
            }
        }
    }

    private function enquiries(): void
    {
        $rows = [
            [EnquiryTopic::Project, 'Sample Property Management LLC', 'We are buying a commercial lot and the lender wants a Phase I environmental site assessment. Can you quote?', EnquiryStatus::New],
            [EnquiryTopic::Project, 'Sample Ranch Partners', 'We found an old fuel tank on our property and would like advice on removal and closure.', EnquiryStatus::New],
            [EnquiryTopic::General, null, 'Do you provide UXO escort for archaeological survey crews?', EnquiryStatus::Answered],
            [EnquiryTopic::Media, 'Sample Community Association', 'Could someone speak at our meeting about the cleanup work near our neighborhood?', EnquiryStatus::Answered],
            [EnquiryTopic::Other, null, 'A question about a past invoice for a sample project.', EnquiryStatus::Closed],
        ];
        foreach ($rows as $i => [$topic, $org, $message, $status]) {
            $e = $this->at(now()->subDays($i * 3 + 1), fn () => Enquiry::create(['topic' => $topic, 'name' => $this->name(), 'organization' => $org, 'email' => 'inquiry'.($i + 1).'@example.com', 'phone' => $this->phone(), 'message' => $message.' (sample)', 'status' => $status]));
        }
    }

    private function rooms(User $owner): void
    {
        $rooms = [
            ['Sample: Sub bid package, barracks sprinkler repair', 'Invited subcontractors (sample)', [['Bid package and instructions to subcontractors', RoomCategory::Solicitation], ['Drawings FP-101 to FP-108', RoomCategory::Drawings], ['Amendment 0001', RoomCategory::Amendments], ['Questions and answers no. 1', RoomCategory::Questions], ['Existing conditions photos', RoomCategory::Photos]]],
            ['Sample: Phase II ESA, county yard', 'Sample County Department', [['Sampling and analysis plan', RoomCategory::Reports], ['Draft Phase II report', RoomCategory::Reports]]],
        ];
        foreach ($rooms as $i => [$name, $client, $docs]) {
            $room = DocumentRoom::create(['name' => $name, 'client_name' => $client, 'client_contact' => $this->name(), 'client_email' => 'client'.($i + 1).'@example.org',
                'description' => 'Sample room for the demo. Documents here are placeholders.', 'created_by' => $owner->id, 'is_demo' => true]);
            $room->log('Room created.', 'system', $owner);
            foreach ($docs as [$title, $cat]) {
                $path = "rooms/{$room->id}/".Str::slug($title).'.pdf';
                $this->pdf($path, $title);
                $room->documents()->create(['title' => $title, 'category' => $cat, 'path' => $path, 'original_name' => Str::slug($title).'.pdf', 'mime' => 'application/pdf', 'size' => 1400, 'uploaded_by' => $owner->id]);
            }
            if ($i === 0) {
                $room->shareLink(30);
                $room->forceFill(['last_opened_at' => now()->subHours(5)])->save();
                $room->log('Share link created, valid until '.$room->link_expires_at?->format('M j, Y').'.', 'system', $owner);
                $room->log('Opened by the client link.');
            }
        }
    }
}
