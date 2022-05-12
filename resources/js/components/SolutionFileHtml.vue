<template>
  <span>
    <b-modal
      :id="`modal-show-html-solution-${currentPage}`"
      ref="htmlModal"
      aria-label="Solution"
      size="lg"
    >
      <div v-html="questions[currentPage-1].solution_html"/>
       <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary"
                  @click="$bvModal.hide(`modal-show-html-solution-${currentPage}`)"
        >
         OK
        </b-button>
      </template>
    </b-modal>
    <b-modal
      :id="`modal-show-audio-solution-${currentPage}`"
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
      >{{ useViewSolutionAsText ? 'View Solution' : standardizeFilename(questions[currentPage - 1].solution) }}</a>
    </span>
    <span v-if="questions[currentPage-1].solution_type === 'q'">
      <a
        :href="questions[currentPage-1].solution_file_url"
        target="_blank"
      >
        {{ useViewSolutionAsText ? 'View Solution' : standardizeFilename(questions[currentPage - 1].solution) }}
      </a>
    </span>
    <a v-if="!['audio','q'].includes(questions[currentPage-1].solution_type)
     && questions[currentPage-1].solution_type === 'html'"
       href=""
       class="btn btn-outline-primary btn-sm link-outline-primary-btn"
       @click.prevent="openShowHTMLSolutionModal"
    >
      View Solution
    </a>

    <span v-if="!questions[currentPage-1].solution && !questions[currentPage-1].solution_html">N/A</span>
  </span>
</template>

<script>
import $ from 'jquery'

export default {
  props: {
    useViewSolutionAsText: {
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
    formatFilename: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    getMaxChildWidth (sel) {
      let max = 0
      let c_width
      $(sel).children().each(function () {
        c_width = parseInt($(this).width())
        if (c_width > max) {
          max = c_width
        }
      })
      return max
    },
    openShowHTMLSolutionModal () {
      this.$bvModal.show(`modal-show-html-solution-${this.currentPage}`)
      this.$nextTick(() => {
        MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        let solutionModal = $(`#modal-show-html-solution-${this.currentPage}`)
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
      this.$bvModal.show(`modal-show-audio-solution-${this.currentPage}`)
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
