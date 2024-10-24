import axios from 'axios'

export async function logout () {
  // Log out the user.
  const { data } = await axios.get('/api/sso/is-sso-user')

  await this.$store.dispatch('auth/logout')
  // For Blackboard, I have to force a new window and use this to tell ADAPT to hide the Breadcrumbs
  Object.keys(localStorage).forEach((key) => {
    if (key !== ('appversion')) {
      delete localStorage[key]
    }
  })
  console.log(Object.keys(localStorage))
  if (data.is_sso_user) {
    window.location = 'https://sso.libretexts.org/cas/logout'
  } else {
    // Redirect to login.
    await this.$router.push({ name: 'home' })
  }
}
