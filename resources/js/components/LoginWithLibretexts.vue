<template>
  <b-button v-if="libretextsAuth" class="btn btn-dark ml-auto" type="button" @click="login">
    <span class="icon-SSO" /> Campus {{ action }}
  </b-button>
</template>

<script>

import axios from 'axios'
import { redirectOnSSOCompletion } from '../helpers/LoginRedirect'

export default {
  name: 'LoginWithLibreTexts',
  props: {
    action: { type: String, default: 'Registration' }
  },
  data: () => ({
    inIFrame: false
  }),
  computed: {
    libretextsAuth: () => window.config.libretextsAuth,
    url: () => `/api/oauth/libretexts`
  },
  mounted () {
    window.addEventListener('message', this.onMessage, false)
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },

  beforeDestroy () {
    window.removeEventListener('message', this.onMessage)
  },

  methods: {
    async login () {
      const newWindow = openWindow('', this.$t('login'))

      const url = await this.$store.dispatch('auth/fetchOauthUrl', {
        provider: 'libretexts'
      })

      newWindow.location.href = url
    },
    removeTimeZoneError () {
      this.form.errors.clear('time_zone')
    },
    /**
     * @param {MessageEvent} e
     */
    async onMessage (e) {
      if (e.origin !== window.origin || !e.data.token) {
        return
      }

      this.$store.dispatch('auth/saveToken', {
        token: e.data.token
      })
      try {
        const { data } = await axios.get('/api/sso/completed-registration')
        console.log(data)
        if (data.registration_type) {
          this.inIFrame ? await this.$router.push({ path: data.landing_page }) : redirectOnSSOCompletion(data.registration_type)
        } else {
          window.location = '/finish-sso-registration'
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}

/**
 * @param  {Object} options
 * @return {Window}
 */
function openWindow (url, title, options = {}) {
  if (typeof url === 'object') {
    options = url
    url = ''
  }

  options = { url, title, width: 600, height: 720, ...options }

  const dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screen.left
  const dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screen.top
  const width = window.innerWidth || document.documentElement.clientWidth || window.screen.width
  const height = window.innerHeight || document.documentElement.clientHeight || window.screen.height

  options.left = ((width / 2) - (options.width / 2)) + dualScreenLeft
  options.top = ((height / 2) - (options.height / 2)) + dualScreenTop

  const optionsStr = Object.keys(options).reduce((acc, key) => {
    acc.push(`${key}=${options[key]}`)
    return acc
  }, []).join(',')

  const newWindow = window.open(url, title, optionsStr)

  if (window.focus) {
    newWindow.focus()
  }

  return newWindow
}
</script>
<style>
@font-face {
  font-family: 'moreicons';
  src: url(https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/icosso.woff) format("woff");
  font-weight: normal;
  font-style: normal;
}

.icon-SSO:after {
  font-family: 'moreicons' !important;
  content: "\e95d";
  font-size: 15px;
}
</style>
