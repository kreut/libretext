<template>
  <div>
    {{ qtiJson }}

    <iframe
      :id="`threeDModel-viewer-${activeIndex}`"
      v-resize="{ log: false }"
      class="threeDModelViewer"
      width="100%"
      :src="src"
      frameborder="0"
      @load="init3DModel(`threeDModel-viewer-${activeIndex}`)"
    />
    <b-alert :show="feedbackType !== ''"
             :variant="feedbackType === 'correct' ? 'success' : 'danger'"
             class="text-center"
    >
      <div v-html="addPrefix(qtiJson.feedback)"/>
    </b-alert>
  </div>
</template>

<script>
import { create3DModelSrc } from '../../helpers/Questions'
import { mapGetters } from 'vuex'
import { v4 as uuidv4 } from 'uuid'

export default {
  name: 'ThreeDModelViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    activeIndex: '',
    feedbackKey: 0,
    feedbackType: '',
    src: '',
    isReady: {}
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  created () {
    window.addEventListener('message', this.receiveMessage, false)
  },
  beforeDestroy () {
    window.removeEventListener('message', this.hotKeys)
  },
  mounted () {
    this.activeIndex = uuidv4()
    this.src = this.create3DModelSrc(this.qtiJson.parameters)
  },
  methods: {
    create3DModelSrc,
    addPrefix(feedback) {
      if (typeof feedback === 'string' && feedback.includes('<p>')) {
        const prefix = this.feedbackType === 'correct' ? 'Correct. ' : 'Incorrect. '
        return feedback.replace('<p>', `<p>${prefix}`)
      }
      return feedback;
    },
    receiveMessage (event) {
      if (event.data.info === 'isReady') {
        const id = event.data.id
        console.error(`${id} is ready`)
        const threeDModelView = document.getElementById(id)
        if (event.data.status === true && id === `threeDModel-viewer-${this.activeIndex}`) {
          this.isReady[id] = true
          if (this.qtiJson && this.qtiJson.studentResponse) {
            const response = {
              type: 'load3DModel',
              modelInfo: {
                selectedIndex: this.qtiJson.studentResponse.selectedIndex
              }
            }
            threeDModelView.contentWindow.postMessage(response, '*')

            if (typeof this.qtiJson.studentResponse !== 'undefined' &&
              typeof this.qtiJson.solutionStructure !== 'undefined' &&
              this.qtiJson.jsonType === 'question_json') {
              this.feedbackType = this.qtiJson.studentResponse.selectedIndex === this.qtiJson.solutionStructure.selectedIndex
                ? 'correct'
                : 'incorrect'
            }
          }
        } else {
          console.log('model not quite ready yet')
        }
      }
    },
    init3DModel (id) {
      this.isReady[id] = false
      const interval = setInterval(() => {
        const threeDModelView = document.getElementById(id)
        if (this.isReady[id] === false && threeDModelView) {
          threeDModelView.contentWindow.postMessage({ message: 'ready', id: id }, '*')
        } else {
          clearInterval(interval)
        }
      }, 50)
    }
  }
}
</script>
