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
import RequiredText from '~/components/RequiredText'
import QuestionCircleTooltip from '~/components/QuestionCircleTooltip'
import vSelect from 'vue-select'
import browserDetect from 'vue-browser-detect-plugin'

Vue.component('v-select', vSelect)
Vue.component('RequiredText', RequiredText)
Vue.component('QuestionCircleTooltip', QuestionCircleTooltip)
Vue.use(browserDetect)

Vue.mixin({
  methods: {
    asset: asset,
    htmlToText: str => str.replace(/<[^>]+>/g, ''),
    zoomGreaterThan: function (zoom) {
      return (window.outerWidth - 8) / window.innerWidth > zoom
    }
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
    if (el.iFrameResizer) {
      el.iFrameResizer.removeListeners()
    }
  }
})

Vue.component('downloadExcel', JsonExcel)
Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)

// accessibility stuff
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

// end accessibility stuff
Vue.use(VueMoment)
Vue.use(AudioRecorder)
Vue.use(VueAnnouncer)

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
  i18n,
  store,
  router,
  ...App
})
