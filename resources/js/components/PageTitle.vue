<template>
  <div>
    <h1 style="font-size:32px" class="page-title">
      {{ title }}
      <FormativeWarning v-if="showFormativeWarning && title"
                        :formative-question="true"
      />
    </h1>
    <span v-if="adaptId">ADAPT ID: <span id="adapt-id">{{ adaptId }}</span>  <span class="text-info">
      <a href=""
         aria-label="Copy ADAPT ID"
         @click.prevent="doCopy('adapt-id')"
      >
        <font-awesome-icon :icon="copyIcon"/>
      </a>
    </span></span>
    <hr>
  </div>
</template>

<script>
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import FormativeWarning from './FormativeWarning.vue'

export default {
  name: 'PageTitle',
  components: {
    FormativeWarning,
    FontAwesomeIcon
  },
  props: {
    title: {
      type: String,
      default: ''
    },
    adaptId: {
      type: String,
      default: ''
    },
    showFormativeWarning: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    copyIcon: faCopy
  }),
  mounted () {
    this.doCopy = doCopy
  }

}
</script>
