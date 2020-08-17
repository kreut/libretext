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

import '~/plugins'
import '~/components'

import 'vuejs-noty/dist/vuejs-noty.css' //https://github.com/renoguyon/vuejs-noty?ref=madewithvuejs.com

import iFrameResize from 'iframe-resizer/js/iframeResizer'

Vue.directive('resize', {
  bind: function (el, {value = {}}) {
    el.addEventListener('load', () => iFrameResize(value, el))
  },
})

Vue.component('downloadExcel', JsonExcel)
Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)
Vue.use(VueNoty)
Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
  i18n,
  store,
  router,
  ...App
})
