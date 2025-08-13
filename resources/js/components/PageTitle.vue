<template>
  <div>
    <h1 style="font-size:32px" class="page-title">
      <b-icon
        v-if="learningTreeId"
        icon="tree"
        variant="success"
      />
      {{ title }}
      <CustomTitle v-if="title && showPencil"
                   :assignment-id="assignmentId"
                   :question-id="questionId"
                   :title="title"
                   style="margin-left:-7px"
                   pencil-class="mb-1"
                   @updateCustomQuestionTitle="updateCustomQuestionTitle"
      />
      <AlgorithmicIcon :algorithmic-question="algorithmicQuestion"
                       :algorithmic-assignment="algorithmicAssignment"
                       :isInstructorWithAnonymousView="isInstructorWithAnonymousView"
                       :isTitle="true"
      />
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
    </span>
    </span>
    <span v-if="learningTreeId"><br>Learning Tree ID: <span id="learning-tree-id">{{ learningTreeId }}</span>  <span
      class="text-info"
    >
      <a href=""
         aria-label="Copy Learning Tree ID"
         @click.prevent="doCopy('learning-tree-id')"
      >
        <font-awesome-icon :icon="copyIcon"/>
      </a>
    </span>
    </span>
    <hr>
  </div>
</template>

<script>
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import FormativeWarning from './FormativeWarning.vue'
import CustomTitle from './CustomTitle.vue'
import AlgorithmicIcon from './AlgorithmicIcon.vue'
export default {
  name: 'PageTitle',
  components: {
    CustomTitle,
    FormativeWarning,
    FontAwesomeIcon,
    AlgorithmicIcon
  },
  props: {
    isInstructorWithAnonymousView: {
      type: Boolean,
      default: false
    },
    algorithmicQuestion: {
      type: Boolean,
      default: false
    },
    algorithmicAssignment: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: ''
    },
    adaptId: {
      type: String,
      default: ''
    },
    learningTreeId: {
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
    cloneSourceId: {
      type: Number,
      default: 0
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
  methods: {
    doCopy,
    updateCustomQuestionTitle (title) {
      this.$emit('updateCustomQuestionTitle', title)
    }
  }
}
</script>
