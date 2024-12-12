import axios from 'axios'

export async function logout () {
  // Log out the user.
  const { data } = await axios.get('/api/sso/is-sso-user')

  await this.$store.dispatch('auth/logout')
  // For Blackboard, I have to force a new window and use this to tell ADAPT to hide the Breadcrumbs
  Object.keys(localStorage).forEach((key) => {
    if (key !== ('appversion') && key !== ('libreOneTester')) {
      delete localStorage[key]
    }
  })
  console.log(Object.keys(localStorage))
  if (data.is_sso_user) {
    window.location = this.environment === 'production'
      ? 'https://auth.libretexts.org/cas/logout'
      : 'https://castest2.libretexts.org/cas/logout'
  } else {
    await this.$router.push({ name: 'welcome' })
  }
}
