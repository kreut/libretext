<template>
  <span>
    <span v-if="questions[currentPage - 1]">
      <b-modal
        :id="`modal-show-html-solution-${modalId}`"
        ref="htmlModal"
        aria-label="Solution"
        size="lg"
        @shown="onHTMLSolutionModalShown"
      >
        <template #modal-header="{ close }">
          <h2 class="editable mb-0">Solution</h2>
          <button type="button" aria-label="Close" class="close" @click="close()">×</button>
        </template>
        <div v-if="imathasSolution">
          <iframe
            :key="`technology-iframe-${questions[currentPage-1].id}-1`"
            v-resize="{ log: false, checkOrigin: false }"
            width="100%"
            :src="imathasSolutionSrc"
            frameborder="0"
          />
        </div>
        <div v-if="questions[currentPage - 1].render_webwork_solution && !renderedWebworkSolution">
          <div class="d-flex justify-content-center mb-3">
            <div class="text-center">
              <b-spinner variant="primary" label="Text Centered"/>
              <span style="font-size:30px" class="text-primary"> Generating Algorithmic Solution...</span>
            </div>
          </div>
        </div>
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
           && (questions[currentPage-1].solution_type === 'html' || renderedWebworkSolution)"
         id="view-solution-button"
         href=""
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
import { applyWarningsVisibility } from '../helpers/CSSUpdates'
import { mapGetters } from 'vuex'
import axios from 'axios'

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
    renderedWebworkSolution: '',
    technologyIframe: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    window.addEventListener('message', this.receiveMessage, false)
  },
  destroyed () {
    window.removeEventListener('message', this.receiveMessage)
  },
  mounted () {
    applyWarningsVisibility(this.user)
    try {
      const url = new URL(this.questions[this.currentPage - 1].technology_iframe)
      url.searchParams.delete('sessionJWT')
      this.technologyIframe = url.toString()
      const problemJWT = url.searchParams.get('problemJWT')
      this.getWebworkSolution(problemJWT)
    } catch (e) {

    }
  },
  methods: {
    async getWebworkSolution (problemJWT) {
      const { data } = await axios.get(`/api/webwork/solution/${problemJWT}`)
      if (data.type === 'error') {
        this.$noty.error(data.message)
      }
      this.renderedWebworkSolution = data.message
    },
    async getIMathASSolution (problemJWT) {
      this.imathasSolution = true
      const imathasDomain = ['dev', 'local'].includes(window.config.environment) ? 'dev2.imathas.libretexts.org' : 'imathas.libretexts.org'
      this.imathasSolutionSrc = `https://${imathasDomain}/imathas/adapt/showdetsoln.php?problemJWT=${problemJWT}`
      await this.openShowHTMLSolutionModal()
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
    onHTMLSolutionModalShown () {
      const modalEl = document.querySelector(`#modal-show-html-solution-${this.modalId}`)
      modalEl.querySelectorAll('script[type="math/tex"]').forEach(el => {
        const math = el.textContent
        const span = document.createElement('span')
        span.textContent = `\\(${math}\\)`
        el.replaceWith(span)
      })
      modalEl.querySelectorAll('script[type="math/tex; mode=display"]').forEach(el => {
        const math = el.textContent
        const span = document.createElement('span')
        span.textContent = `\\[${math}\\]`
        el.replaceWith(span)
      })
      this.typesetMath(modalEl)
    },
    async openShowHTMLSolutionModal () {
      if (!this.renderedWebworkSolution && this.questions[this.currentPage - 1].render_webwork_solution) {
        const url = new URL(this.questions[this.currentPage - 1].technology_iframe)
        const problemJWT = url.searchParams.get('problemJWT')
        await this.getWebworkSolution(problemJWT)
      }
      this.$bvModal.show(`modal-show-html-solution-${this.modalId}`)
      this.$nextTick(() => {
        let solutionModal = $(`#modal-show-html-solution-${this.modalId}`)
        if (solutionModal.length) {
          let images = solutionModal.find('img')
          for (let i = 0; i < images.length; i++) {
            images[i].style.maxWidth = '100%'
          }
          let maxChildWidth = this.getMaxChildWidth(solutionModal.find('.mt-section')[0])
          if (document.getElementsByClassName('mt-section').length) {
            let elem, style
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
