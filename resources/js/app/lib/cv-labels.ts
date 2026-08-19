import { BRAND_NAME_TM } from '@shared/lib/brand';

export type CvLocale = 'en' | 'de';

export type CvLabelSet = {
    aboutMe: string;
    workExperience: string;
    educationAndTraining: string;
    languageSkills: string;
    languagesKnown: string;
    languageTypeRegional: string;
    languageTypeRegionalForeign: string;
    languageTypeForeign: string;
    motherTongue: string;
    otherLanguages: string;
    contact: string;
    placeOfBirth: string;
    fresherNoExperience: string;
    fullFresherNoExperience: string;
    currentPlans: string;
    noExperience: string;
    organizationFallback: string;
    noQualifications: string;
    qualificationFallback: string;
    noPhoto: string;
    dob: string;
    nationality: string;
    gender: string;
    certification: string;
    license: string;
    achievements: string;
    skills: string;
    hobbies: string;
    languageSkillsHeading: string;
    emptyDash: string;
    createdAt: string;
    passportPhotoAlt: string;
    brandAlt: string;
    // Full CV
    lastName: string;
    firstName: string;
    citizenship: string;
    civilStatus: string;
    currentAddress: string;
    homeAddress: string;
    emailAddress: string;
    mobileNumber: string;
    driversLicense: string;
    fullBodyPhotoAlt: string;
    fullBodyPlaceholder: string;
    profile: string;
    educationalBackground: string;
    trainingAndSeminars: string;
    licensesAndQualifications: string;
    certifications: string;
    presentations: string;
    publications: string;
    professionalExperience: string;
    reasonsForGap: string;
    languageOfInstruction: string;
    registrationNumber: string;
    issuedDate: string;
    validTill: string;
    noExperienceLetter: string;
    bedCapacity: string;
    departmentsOffered: string;
    accreditations: string;
    criticalCases: string;
    medicalEquipment: string;
    casesHandled: string;
};

export const cvLabels: Record<CvLocale, CvLabelSet> = {
    en: {
        aboutMe: 'About me',
        workExperience: 'Work experience',
        educationAndTraining: 'Education and training',
        languageSkills: BRAND_NAME_TM,
        languagesKnown: 'Languages Known',
        languageTypeRegional: 'Regional',
        languageTypeRegionalForeign: 'Regional/Foreign',
        languageTypeForeign: 'Foreign Language',
        motherTongue: 'Mother tongue(s)',
        otherLanguages: 'Other language(s)',
        contact: 'Contact',
        placeOfBirth: 'Place of birth',
        fresherNoExperience:
            'Fresher — no professional experience listed yet.',
        fullFresherNoExperience:
            'No professional experience as the candidate is a fresher.',
        currentPlans: 'Current plans:',
        noExperience: 'No experience added yet.',
        organizationFallback: 'Organization',
        noQualifications: 'No qualifications added yet.',
        qualificationFallback: 'Qualification',
        noPhoto: 'No photo',
        dob: 'Date of birth',
        nationality: 'Nationality',
        gender: 'Gender',
        certification: 'Certification',
        license: 'License',
        achievements: 'Achievements',
        skills: 'Skills',
        hobbies: 'Hobbies',
        languageSkillsHeading: 'Language Skills',
        emptyDash: '—',
        createdAt: 'Created at',
        passportPhotoAlt: 'passport photo',
        brandAlt: BRAND_NAME_TM,
        lastName: 'Last Name',
        firstName: 'First Name',
        citizenship: 'Citizenship',
        civilStatus: 'Civil Status',
        currentAddress: 'Current Address',
        homeAddress: 'Home Address',
        emailAddress: 'Email Address',
        mobileNumber: 'Mobile Number',
        driversLicense: "Driver's License",
        fullBodyPhotoAlt: 'Full body photo',
        fullBodyPlaceholder: 'Full body photo not uploaded',
        profile: 'Profile',
        educationalBackground: 'Educational Background',
        trainingAndSeminars: 'Training and Seminars',
        licensesAndQualifications: 'Licenses and Professional Qualifications',
        certifications: 'Certifications',
        presentations: 'Presentations',
        publications: 'Publications',
        professionalExperience: 'Professional Experience',
        reasonsForGap: 'Reasons for Gap in Practice / Education',
        languageOfInstruction: 'Language of Instruction',
        registrationNumber: 'Registration Number',
        issuedDate: 'Issued Date',
        validTill: 'Valid till',
        noExperienceLetter: 'No experience letter available',
        bedCapacity: 'Bed capacity',
        departmentsOffered: 'Departments offered',
        accreditations: 'Accreditations',
        criticalCases: 'Critical cases handled',
        medicalEquipment: 'Medical equipment',
        casesHandled: 'Cases handled',
    },
    de: {
        aboutMe: 'Über mich',
        workExperience: 'Berufserfahrung',
        educationAndTraining: 'Ausbildung',
        languageSkills: BRAND_NAME_TM,
        languagesKnown: 'Bekannte Sprachen',
        languageTypeRegional: 'Regional',
        languageTypeRegionalForeign: 'Regional/Fremdsprache',
        languageTypeForeign: 'Fremdsprache',
        motherTongue: 'Muttersprache(n)',
        otherLanguages: 'Fremdsprache(n)',
        contact: 'Kontakt',
        placeOfBirth: 'Geburtsort',
        fresherNoExperience:
            'Berufseinsteiger — noch keine Berufserfahrung angegeben.',
        fullFresherNoExperience:
            'Keine Berufserfahrung — die Kandidatin / der Kandidat ist Berufseinsteiger.',
        currentPlans: 'Aktuelle Pläne:',
        noExperience: 'Noch keine Berufserfahrung hinzugefügt.',
        organizationFallback: 'Organisation',
        noQualifications: 'Noch keine Qualifikationen hinzugefügt.',
        qualificationFallback: 'Qualifikation',
        noPhoto: 'Kein Foto',
        dob: 'Geburtsdatum',
        nationality: 'Staatsangehörigkeit',
        gender: 'Geschlecht',
        certification: 'Zertifizierungen',
        license: 'Lizenz',
        achievements: 'Leistungen',
        skills: 'Fähigkeiten',
        hobbies: 'Hobbys',
        languageSkillsHeading: 'Sprachkenntnisse',
        emptyDash: '—',
        createdAt: 'Erstellt bei',
        passportPhotoAlt: 'Passfoto',
        brandAlt: BRAND_NAME_TM,
        lastName: 'Nachname',
        firstName: 'Vorname',
        citizenship: 'Staatsangehörigkeit',
        civilStatus: 'Familienstand',
        currentAddress: 'Aktuelle Adresse',
        homeAddress: 'Heimatadresse',
        emailAddress: 'E-Mail-Adresse',
        mobileNumber: 'Mobilnummer',
        driversLicense: 'Führerschein',
        fullBodyPhotoAlt: 'Ganzkörperfoto',
        fullBodyPlaceholder: 'Ganzkörperfoto nicht hochgeladen',
        profile: 'Profil',
        educationalBackground: 'Ausbildung',
        trainingAndSeminars: 'Fortbildungen und Seminare',
        licensesAndQualifications: 'Lizenzen und berufliche Qualifikationen',
        certifications: 'Zertifizierungen',
        presentations: 'Präsentationen',
        publications: 'Publikationen',
        professionalExperience: 'Berufserfahrung',
        reasonsForGap: 'Gründe für Lücken in Praxis / Ausbildung',
        languageOfInstruction: 'Unterrichtssprache',
        registrationNumber: 'Registrierungsnummer',
        issuedDate: 'Ausstellungsdatum',
        validTill: 'Gültig bis',
        noExperienceLetter: 'Kein Erfahrungsnachweis vorhanden',
        bedCapacity: 'Bettenkapazität',
        departmentsOffered: 'Angebotene Abteilungen',
        accreditations: 'Akkreditierungen',
        criticalCases: 'Behandelte kritische Fälle',
        medicalEquipment: 'Medizinische Geräte',
        casesHandled: 'Behandelte Fälle',
    },
};

const civilStatusDe: Record<string, string> = {
    Unmarried: 'Ledig',
    Married: 'Verheiratet',
};

const genderDe: Record<string, string> = {
    Female: 'Weiblich',
    Male: 'Männlich',
    Other: 'Divers',
    'Prefer not to say': 'Keine Angabe',
};

const proficiencyDe: Record<string, string> = {
    Beginner: 'Anfänger',
    Intermediate: 'Fortgeschritten',
    Professional: 'Berufliche Kenntnisse',
    Native: 'Muttersprache',
};

export function localizeCivilStatus(
    value: string | null | undefined,
    locale: CvLocale,
): string {
    if (!value) {
        return '';
    }

    if (locale === 'de') {
        return civilStatusDe[value] ?? value;
    }

    return value;
}

export function localizeGender(
    value: string | null | undefined,
    locale: CvLocale,
): string {
    if (!value) {
        return '';
    }

    if (locale === 'de') {
        return genderDe[value] ?? value;
    }

    return value;
}

export function localizeProficiency(
    value: string | null | undefined,
    locale: CvLocale,
): string {
    if (!value) {
        return '';
    }

    if (locale === 'de') {
        return proficiencyDe[value] ?? value;
    }

    return value;
}
