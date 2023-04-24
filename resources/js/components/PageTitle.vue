<template>
  <div>
    <div style="font-size:32px" class="page-title">
      {{ title }}
      <CustomTitle v-if="title && showPencil"
                   :assignment-id="assignmentId"
                   :question-id="questionId"
                   :title="title"
                   style="margin-left:-7px"
                   pencil-class="mb-1"
                   @updateCustomQuestionTitle="updateCustomQuestionTitle"
      />
      <FormativeWarning v-if="showFormativeWarning && title"
                        :formative-question="true"
      />
    </div>
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
import CustomTitle from './CustomTitle.vue'

export default {
  name: 'PageTitle',
  components: {
    CustomTitle,
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
    },
    showPencil: {
      type: Boolean,
      default: false
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    copyIcon: faCopy
  }),
  mounted () {
    this.doCopy = doCopy
  },
  methods: {
    updateCustomQuestionTitle (title) {
      this.$emit('updateCustomQuestionTitle', title)
    }
  }
}
</script>
