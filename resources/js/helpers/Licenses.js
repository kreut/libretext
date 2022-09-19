export function updateLicenseVersions (license) {
  this.licenseVersionOptions = this.defaultLicenseVersionOptions.filter(version => version.licenses.includes(license))
  let licenseVersion = null
  if (license !== null) {
    if (['ccby', 'ccbyncnd', 'ccbynd', 'ccbysa', 'ccbyncsa', 'ccbync', 'imathascomm'].includes(license)) {
      licenseVersion = '4.0'
    } else if (license === 'gnufdl') {
      licenseVersion = '1.3'
    } else if (license === 'gnu') {
      licenseVersion = '3.0'
    }
  }
  return licenseVersion
}

let licenseOptions = [
  { value: null, text: 'Please choose a license...' },
  { value: 'publicdomain', text: 'Public Domain', url: 'https://creativecommons.org/licenses/Public_domain' },
  { value: 'publicdomaindedication', text: 'CC0 1.0', url: 'https://creativecommons.org/publicdomain/zero/1.0/' },
  { value: 'ccpdm', text: 'CC PDM', url: 'https://creativecommons.org/publicdomain/mark/1.0/' },
  { value: 'ccby', text: 'CC BY', url: 'https://creativecommons.org/licenses/by' },
  { value: 'ccbynd', text: 'CC BY-ND', url: 'https://creativecommons.org/licenses/by-nd' },
  { value: 'ccbync', text: 'CC BY-NC', url: 'https://creativecommons.org/licenses/by-nc' },
  { value: 'ccbyncnd', text: 'CC BY-NC-ND', url: 'https://creativecommons.org/licenses/by-nc-nd' },
  { value: 'ccbyncsa', text: 'CC BY-NC-SA', url: 'https://creativecommons.org/licenses/by-nc-sa' },
  { value: 'ccbysa', text: 'CC BY-SA', url: 'https://creativecommons.org/licenses/by-sa' },
  { value: 'gnu', text: 'GNU GPL', url: 'https://www.gnu.org/licenses/gpl-' },
  { value: 'arr', text: 'All Rights Reserved' },
  { value: 'gnufdl', text: 'GNU FDL', url: 'https://www.gnu.org/licenses/fdl-' },
  {
    value: 'opl_license',
    text: 'OPL',
    url: 'https://github.com/openwebwork/webwork-open-problem-library/blob/master/OPL_LICENSE'
  },
  {
    value: 'imathascomm',
    text: 'IMathAS Community',
    url: 'https://www.imathas.com/communitylicense.html'
  },
  {
    value: 'ck12foundation',
    text: 'CK-12 Foundation Curriculum Materials License',
    url: 'https://www.ck12info.org/curriculum-materials-license/'
  }
]

let defaultLicenseVersionOptions = [
  {
    value: '4.0',
    text: '4.0',
    licenses: ['ccby', 'ccbyncnd', 'ccbynd', 'ccbysa', 'ccbyncsa', 'ccbync', 'imathascomm']
  },
  { value: '3.0', text: '3.0', licenses: ['gnu', 'ccby', 'ccbyncnd', 'ccbyncsa', 'ccbynd', 'ccbysa', 'ccbync'] },
  { value: '2.5', text: '2.5', licenses: ['ccby', 'ccbyncnd', 'ccbynd', 'ccbysa', 'ccbync'] },
  { value: '2.0', text: '2.0', licenses: ['gnu', 'ccby', 'ccbyncnd', 'ccbynd', 'ccbysa', 'ccbync'] },
  { value: '1.3', text: '1.3', licenses: ['gnufdl'] },
  { value: '1.2', text: '1.2', licenses: ['gnufdl'] },
  { value: '1.1', text: '1.1', licenses: ['gnufdl'] },
  { value: '1.0', text: '1.0', licenses: ['gnu', 'ccby', 'ccbyncnd', 'ccbynd', 'ccbysa', 'ccbync'] }
]

export { licenseOptions, defaultLicenseVersionOptions }
