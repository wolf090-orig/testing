// Константы для кодов стран
export const COUNTRY_CODES = {
  RUSSIA: 'RU',
  UKRAINE: 'UA',
  BELARUS: 'BY',
  KAZAKHSTAN: 'KZ',
  UZBEKISTAN: 'UZ'
} as const;

// Страна по умолчанию
export const DEFAULT_COUNTRY_CODE = COUNTRY_CODES.UZBEKISTAN;

// Список всех поддерживаемых стран
export const SUPPORTED_COUNTRIES = [
  { code: 'UZ', name: 'Uzbekistan', name_ru: 'Узбекистан', name_uz: 'O\'zbekiston' },
  { code: 'RU', name: 'Russia', name_ru: 'Россия', name_uz: 'Rossiya' },
  { code: 'UA', name: 'Ukraine', name_ru: 'Украина', name_uz: 'Ukraina' },
  { code: 'BY', name: 'Belarus', name_ru: 'Беларусь', name_uz: 'Belarus' },
  { code: 'KZ', name: 'Kazakhstan', name_ru: 'Казахстан', name_uz: 'Qozog\'iston' }
];

// Поддерживаемые языки
export const SUPPORTED_LANGUAGES = [
  { code: 'ru', name: 'Русский', name_native: 'Русский' },
  { code: 'uz', name: 'O\'zbekcha', name_native: 'O\'zbekcha' },
  { code: 'en', name: 'English', name_native: 'English' }
];

export const DEFAULT_LANGUAGE_CODE = 'ru'; 