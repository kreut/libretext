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

import iFrameResize from 'iframe-resizer/js/iframeResizer'

import VueMoment from 'vue-moment'

VueClipboard.config.autoSetContainer = true // add this line
Vue.use(VueClipboard)
Vue.component(VueCountdown.name, VueCountdown)

Vue.directive('resize', {
  bind: function (el, { value = {} }) {
    el.addEventListener('load', () => iFrameResize(value, el))
  }
})

Vue.component('downloadExcel', JsonExcel)
Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)
Vue.use(VueNoty)
Vue.use(VueMoment)
Vue.use(AudioRecorder)

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
  i18n,
  store,
  router,
  ...App
})
