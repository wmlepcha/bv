export type CvExperience = {
    duration: string | null;
    start_date: string | null;
    end_date: string | null;
    designation: string | null;
    department: string | null;
    organization: string | null;
    city: string | null;
    state: string | null;
    cases_handled: string | null;
    bed_capacity: string | null;
    medical_equipment: string | null;
    departments_offered: string | null;
    accreditations: string | null;
    critical_cases: string | null;
    has_experience_letter: boolean;
    certificate_path: string | null;
};

export type CvQualification = {
    start_date: string | null;
    end_date: string | null;
    title: string | null;
    college: string | null;
    university: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    board: string | null;
    language_of_instruction: string | null;
    certificate_path: string | null;
};

export type CvTraining = {
    date: string | null;
    topic: string | null;
    organization_location: string | null;
    certificate_path: string | null;
};

export type CvActivityEntry = {
    date: string | null;
    topic: string | null;
    organization: string | null;
    location: string | null;
    evidence_type: 'file' | 'link' | null;
    certificate_path: string | null;
    link_url: string | null;
};

export type CvCertification = {
    name: string | null;
    description: string | null;
    certificate_path: string | null;
};

export type CvLicense = {
    title: string | null;
    registration_number: string | null;
    issued_date: string | null;
    valid_till: string | null;
    certificate_path: string | null;
};

export type CvBilingualEntry = {
    language: string | null;
    language_type: string | null;
    proficiency: string | null;
    certification_level: string | null;
    certificates: string | null;
    certificate_path: string | null;
};

export type CvVitae = {
    is_newbie: boolean;
    experiences: CvExperience[];
    qualifications: CvQualification[];
    trainings: CvTraining[];
    seminars: CvActivityEntry[];
    presentations: CvActivityEntry[];
    publications: CvActivityEntry[];
    certifications: CvCertification[];
    licenses: CvLicense[];
    bilingual: CvBilingualEntry[];
};

export type CvProfile = {
    candidate_id: string | null;
    first_name: string | null;
    last_name: string | null;
    name: string;
    email: string;
    state_code: string | null;
    state_name: string | null;
    special_id: string | null;
    special_id_label: string | null;
    registration_year: number | null;
    mobile_whatsapp: string | null;
    date_of_birth: string | null;
    place_of_birth: string | null;
    civil_status: string | null;
    gender: string | null;
    secondary_email: string | null;
    secondary_mobile: string | null;
    current_address: string | null;
    permanent_address: string | null;
    nationality: string | null;
    passport_or_id_number: string | null;
    passport_document_url: string | null;
    passport_document_path: string | null;
    drivers_license: string | null;
    drivers_license_document_url: string | null;
    drivers_license_document_path: string | null;
    passport_photo_url: string | null;
    full_body_photo_url: string | null;
    passport_photo_path: string | null;
    full_body_photo_path: string | null;
    introduction: string | null;
    hobbies: string | null;
    skills: string[];
    gap_reasons: string | null;
    current_status: string | null;
    pursuing_details: string | null;
    headline: string | null;
    languages: string[];
    proficiency_summary: string | null;
    vitae: CvVitae;
};
