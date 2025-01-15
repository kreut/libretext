export function updateLibreOneProfile () {
  window.location.href = this.environment === 'production'
    ? 'https://one.libretexts.org/profile'
    : 'https://staging.one.libretexts.org/profile'
}

export function updateLibreOnePassword () {
  window.location.href = this.environment === 'production'
    ? 'https://one.libretexts.org/security'
    : 'https://staging.one.libretexts.org/security'
}
