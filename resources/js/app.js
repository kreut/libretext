import Vue from 'vue'
import store from '~/store'
import router from '~/router'
import i18n from '~/plugins/i18n'
import App from '~/components/App'
import { BootstrapVue, BootstrapVueIcons } from 'bootstrap-vue'
import VueNoty from 'vuejs-noty'

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'


import JsonExcel from 'vue-json-excel'
import VueCountdown from '@chenfengyuan/vue-countdown'
import AudioRecorder from 'vue-audio-recorder'
import '~/plugins'
import '~/components'
import VueClipboard from 'vue-clipboard2'
import 'vuejs-noty/dist/vuejs-noty.css' // https://github.com/renoguyon/vuejs-noty?ref=madewithvuejs.com

import VueAnnouncer from '@vue-a11y/announcer'

import iFrameResize from 'iframe-resizer/js/iframeResizer'

import VueMoment from 'vue-moment'

import { asset } from '@codinglabs/laravel-asset'
import axios from 'axios'

Vue.mixin({
  methods: {
    asset: asset,
    htmlToText: str => str.replace(/<[^>]+>/g, '')
  }
})

VueClipboard.config.autoSetContainer = true // add this line
Vue.use(VueClipboard)
Vue.component(VueCountdown.name, VueCountdown)


Vue.directive('resize', {
  bind: function (el, { value = {} }) {
    el.addEventListener('load', () => iFrameResize(value, el))
  },
  unbind: function (el) {
    el.iFrameResizer.removeListeners()
  }
})

Vue.component('downloadExcel', JsonExcel)
Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)

Vue.use(VueNoty, {
  callbacks: {
    onShow: function () {
      document.getElementsByClassName('noty_body')[0].setAttribute('role', 'alert')
    },
    onClose: function () {
      document.getElementsByClassName('noty_body')[0].setAttribute('role', '')
    }
  }
})
Vue.use(VueMoment)
Vue.use(AudioRecorder)
Vue.use(VueAnnouncer)

Vue.config.productionTip = false

/* eslint-disable no-new */
if (window.location.pathname.search('questions/view') !== -1) {
  let urlPieces = window.location.pathname.split('/')
  // ["", "assignments", "1193", "questions", "view"]
  let assignmentId = urlPieces[2]
  let questionId = urlPieces[5] ? urlPieces[5] : null
  let shownSections = urlPieces[6] ? urlPieces[6] : null
  axios.get(
    `/api/beta-assignments/get-from-alpha-assignment/${assignmentId}`)
    .then(function (response) {
      if (response.data.type !== 'success') {
        let message = response.data.message ? response.data.message : response.data
        alert(message)
        console.log(response)
        window.location = '/beta-assignments/redirect-error'
      }
      if (response.data.login_redirect) {
        window.location = '/login'
      } else if (response.data.beta_assignment_id) {
        let url = `/assignments/${response.data.beta_assignment_id}/questions/view`
        if (questionId) {
          url += `/${questionId}`
        }
        if (shownSections) {
          url += `/${shownSections}`
        }
        window.location = url
      } else {
        new Vue({
          i18n,
          store,
          router,
          ...App
        })
      }
    }).catch(error => {
    alert(error)
    window.location = '/beta-assignments/redirect-error'
    console.log(error)
  })
} else {
  new Vue({
    i18n,
    store,
    router,
    ...App
  })
}
