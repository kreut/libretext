let licenseOptions = [
  { value: null, text: 'Choose a license...' },
  { value: 'publicdomain', text: 'Public Domain', url: 'https://creativecommons.org/licenses/Public_domain' },
  { value: 'publicdomaindedication', text: 'CC0 1.0', url: 'https://creativecommons.org/publicdomain/zero/1.0/' },
  { value: 'ccby', text: 'CC BY', url: 'https://creativecommons.org/licenses/by' },
  { value: 'ccbynd', text: 'CC BY-ND', url: 'https://creativecommons.org/licenses/by-nd' },
  { value: 'ccbync', text: 'CC BY-NC', url: 'https://creativecommons.org/licenses/by-nc' },
  { value: 'ccbyncnd', text: 'CC BY-NC-ND', url: 'https://creativecommons.org/licenses/by-nc-nd' },
  { value: 'ccbyncsa', text: 'CC BY-NC-SA', url: 'https://creativecommons.org/licenses/by-nc-sa' },
  { value: 'gnu', text: 'GNU GPL', url: 'https://www.gnu.org/licenses/gpl-' },
  { value: 'arr', text: 'All Rights Reserved' },
  { value: 'gnufdl', text: 'GNU FDL', url: 'https://www.gnu.org/licenses/fdl-' },
  {
    value: 'opl_license',
    text: 'OPL',
    url: 'https://github.com/openwebwork/webwork-open-problem-library/blob/master/OPL_LICENSE'
  }
]

let defaultLicenseVersionOptions = [
  { value: '4.0', text: '4.0', licenses: ['ccby', 'ccbyncnd', 'ccbynd', 'ccbysa'] },
  { value: '3.0', text: '3.0', licenses: ['gnu', 'ccby', 'ccbyncnd', 'ccbyncsa', 'ccbynd', 'ccbysa'] },
  { value: '2.5', text: '2.5', licenses: ['ccby', 'ccbyncnd', 'ccbynd', 'ccbysa'] },
  { value: '2.0', text: '2.0', licenses: ['gnu', 'ccby', 'ccbyncnd', 'ccbynd', 'ccbysa'] },
  { value: '1.3', text: '1.3', licenses: ['gnufdl'] },
  { value: '1.2', text: '1.2', licenses: ['gnufdl'] },
  { value: '1.1', text: '1.1', licenses: ['gnufdl'] },
  { value: '1.0', text: '1.0', licenses: ['gnu', 'ccby', 'ccbyncnd', 'ccbynd', 'ccbysa'] }
]

export { licenseOptions, defaultLicenseVersionOptions }
