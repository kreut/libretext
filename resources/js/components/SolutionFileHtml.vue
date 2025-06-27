<template>
  <span>
    <span v-if="questions[currentPage - 1]">
      <b-modal
        :id="`modal-show-html-solution-${modalId}`"
        ref="htmlModal"
        aria-label="Solution"
        size="lg"
      ><div v-if="imathasSolution">
         <h2 class="editable">Solution</h2>
         <iframe
           :key="`technology-iframe-${questions[currentPage-1].id}-1`"
           v-resize="{ log: false, checkOrigin: false }"
           width="100%"
           :src="imathasSolutionSrc"
           frameborder="0"
         />
       </div>
        <iframe
          v-show="false"
          :key="`technology-iframe-${questions[currentPage-1].id}-1`"
          v-resize="{ log: false, checkOrigin: false }"
          width="100%"
          :src="questions[currentPage-1].technology_iframe"
          frameborder="0"
        />
        <iframe
          v-show="false"
          :key="`technology-iframe-src-${questions[currentPage-1].id}-1`"
          v-resize="{ log: false, checkOrigin: false }"
          width="100%"
          :src="questions[currentPage-1].technology_iframe_src"
          frameborder="0"
        />
        <div v-if="questions[currentPage - 1].render_webwork_solution && !renderedWebworkSolution">
          <div class="d-flex justify-content-center mb-3">
            <div class="text-center">
              <b-spinner variant="primary" label="Text Centered"/>
              <span style="font-size:30px" class="text-primary"> Generating Algorithmic Solution...</span>
            </div>
          </div>
        </div>
        <h2 v-if="isPreviewSolutionHtml && !renderedWebworkSolution && !questions[currentPage-1].imathas_solution"
            class="editable"
        >Solution</h2>
        <div v-html="renderedWebworkSolution"/>
        <div v-if="!renderedWebworkSolution && !questions[currentPage - 1].render_webwork_solution"
             v-html="questions[currentPage-1].solution_html"
        />
        <template #modal-footer="{ ok }">
          <b-button size="sm" variant="primary"
                    @click="$bvModal.hide(`modal-show-html-solution-${modalId}`)"
          >
            OK
          </b-button>
        </template>
      </b-modal>
      <b-modal
        :id="`modal-show-audio-solution-${modalId}`"
        ref="modal"
        title="Audio Solution"
        ok-title="OK"
        ok-only
        :size="questions[currentPage-1].solution_text ? 'lg' : 'md'"
      >
        <b-row align-h="center">
          <b-card>
            <audio-player
              :src="questions[currentPage-1].solution_file_url"
            />
          </b-card>
        </b-row>
        <div v-if="questions[currentPage-1].solution_text" class="pt-3">
          <span v-html="questions[currentPage-1].solution_text"/>
        </div>
      </b-modal>
      <span v-if="questions[currentPage-1].solution_type === 'audio'">
        <a
          href=""
          class="btn btn-outline-primary btn-sm link-outline-primary-btn"
          @click="openShowAudioSolutionModal"
        >{{
            useViewSolutionAsText ? 'View Solution' : standardizeFilename(questions[currentPage - 1].solution)
          }}</a>
      </span>
      <span v-if="questions[currentPage-1].solution_type === 'q'">
        <a
          :href="questions[currentPage-1].solution_file_url"
          target="_blank"
        >
          {{ useViewSolutionAsText ? 'failed Solution' : standardizeFilename(questions[currentPage - 1].solution) }}
        </a>
      </span>
      <a v-if="!['audio','q'].includes(questions[currentPage-1].solution_type)
           && (questions[currentPage-1].solution_type === 'html')"
         href=""
         id="view-solution-button"
         class="btn btn-outline-primary link-outline-primary-btn"
         :class="`btn-${buttonSize}`"
         v-show="showButton"
         @click.prevent="questions[currentPage-1].imathas_solution ? getIMathASSolution(questions[currentPage-1].problem_jwt) : openShowHTMLSolutionModal()"
      >
       View Solution
      </a>
      <span
        v-if="showNa && !questions[currentPage-1].solution_type && !questions[currentPage-1].render_webwork_solution"
      >N/A</span>
    </span>
  </span>
</template>

<script>
import $ from 'jquery'
import { webworkOnLoadCssUpdates } from '../helpers/CSSUpdates'

export default {
  props: {
    buttonSize: {
      type: String,
      default: 'sm'
    },
    showButton: {
      type: Boolean,
      default: true
    },
    modalId: {
      type: String,
      default: 'some-id'
    },
    useViewSolutionAsText: {
      type: Boolean,
      default: false
    },
    isPreviewSolutionHtml: {
      type: Boolean,
      default: false
    },
    questions: {
      type: Array,
      default: null
    },
    currentPage: {
      type: Number,
      default: null
    },
    assignmentName: {
      type: String,
      default: 'Assignment'
    },
    showNa: {
      type: Boolean,
      default: true
    },
    formatFilename: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    imathasSolution: '',
    imathasSolutionSrc: '',
    renderedWebworkSolution: ''
  }),
  created () {
    window.addEventListener('message', this.receiveMessage, false)
  },
  destroyed () {
    window.removeEventListener('message', this.receiveMessage)
  },
  methods: {
    async getIMathASSolution (problemJWT) {
      console.log(window.config.environment)
      console.log(problemJWT)
      this.imathasSolution = true
      const imathasDomain = ['dev', 'local'].includes(window.config.environment) ? 'dev2.imathas.libretexts.org' : 'imathas.libretexts.org'
      this.imathasSolutionSrc = `https://${imathasDomain}/imathas/adapt/showdetsoln.php?problemJWT=${problemJWT}`
      this.openShowHTMLSolutionModal()
    },
    receiveMessage (event) {
      if (this.questions[this.currentPage - 1] && this.questions[this.currentPage - 1].render_webwork_solution) {
        if (event.data === 'loaded') {
          event.source.postMessage(JSON.stringify(webworkOnLoadCssUpdates), event.origin)
        } else {
          try {
            console.error(event)
            console.error(event.data)
            let jsonObj = JSON.parse(event.data)
            console.log(jsonObj.solutions)
            if (jsonObj.solutions.length) {
              this.renderedWebworkSolution = '<h2 class="editable">Solution</h2>'
              for (let i = 0; i < jsonObj.solutions.length; i++) {
                this.renderedWebworkSolution += jsonObj.solutions[i]
              }
            }
            this.$nextTick(() => {
              MathJax.Hub.Queue(['Typeset', MathJax.Hub])
            })
            console.log(this.renderedWebworkSolution)
          } catch (error) {

          }
        }
      }
    },
    getMaxChildWidth (sel) {
      let max = 0
      let cWidth
      $(sel).children().each(function () {
        cWidth = parseInt($(this).width())
        if (cWidth > max) {
          max = cWidth
        }
      })
      return max
    },
    openShowHTMLSolutionModal () {
      this.renderedWebworkSolution = ''
      this.$bvModal.show(`modal-show-html-solution-${this.modalId}`)
      this.$nextTick(() => {
        MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        let solutionModal = $(`#modal-show-html-solution-${this.modalId}`)
        if (solutionModal.length) {
          solutionModal.find('.modal-header')[0].style.display = 'none'
          let images = solutionModal.find('img')
          for (let i = 0; i < images.length; i++) {
            images[i].style.maxWidth = '100%'
          }
          let maxChildWidth = this.getMaxChildWidth(solutionModal.find('.mt-section')[0])
          if (document.getElementsByClassName('mt-section').length) {
            let elem,
              style
            elem = document.querySelector('.modal-lg')
            style = getComputedStyle(elem)
            if (parseInt(maxChildWidth) > parseInt(style.width)) {
              let selector = solutionModal[0]
              selector.getElementsByClassName('modal-lg')[0].style.maxWidth = Math.min(parseInt(maxChildWidth), window.outerWidth) - 20 + 'px'
              selector.getElementsByClassName('modal-body')[0].style.overflowX = 'auto'
            }
          }
        }
      })
    },
    standardizeFilename (filename) {
      if (this.formatFilename) {
        if (!filename) {
          return ''
        }
        let ext = filename.slice((Math.max(0, filename.lastIndexOf('.')) || Infinity) + 1)

        let name = this.assignmentName.replace(/[/\\?%*:|"<>]/g, '-')
        return `${name}-${this.currentPage}.${ext}`
      } else {
        return filename
      }
    },
    openShowAudioSolutionModal (event) {
      event.preventDefault()
      this.$bvModal.show(`modal-show-audio-solution-${this.modalId}`)
    }
  }
}
</script>
<style>
.MathJax_Display, .MJXc-display, .MathJax_SVG_Display {
  overflow-x: auto;
  overflow-y: hidden;
}
</style>
