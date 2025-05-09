<template>
  <div>
    <b-modal id="modal-single-marker-sketcher-viewer"
             title="View Mark"
             no-close-on-backdrop
             size="lg"
    >
      <b-alert show :variant="answeredCorrectly ? 'success' : 'danger'">
        {{ message }}
      </b-alert>
      <iframe
        id="single-marker-sketcher-viewer"
        v-resize="{ log: false }"
        width="100%"
        src="/api/sketcher/show-correct"
        frameborder="0"
        @load="loadStructure"
      />
    </b-modal>
    <div v-if="isInclusive">
      <div>
        Total Points: {{ question.submission_score }}
      </div>
      <div class="mb-2">
        Total Percentage: {{ (totalPercentScore.toFixed(0) * 1).toString() }}%
      </div>
    </div>
    <b-table
      aria-label="'Atoms and Bonds"
      striped
      hover
      :no-border-collapse="true"
      :fields="studentResponseFields"
      :items="studentResponse"
      small
    >
      <template v-slot:head(symbol)>
        Label
        <QuestionCircleTooltip :id="'label-tooltip'" />
        <b-tooltip target="label-tooltip" triggers="hover focus" delay="500">
          Clicking on any atom/bond will show you where it's located in the molecule.
        </b-tooltip>
      </template>
      <template v-slot:cell(symbol)="data">
        <b-button :id="`label-${data.item.index}`"
                  :variant="data.item.correct ? 'success' : 'danger'"
                  class="ml-2"
                  @click="initLoadStructure(data.item.index,data.item.structuralComponent)"
        >
          {{ data.item.structuralComponent === 'atom' ? data.item.symbol : data.item.type }}
        </b-button>
      </template>
      <template v-slot:cell(feedback)="data">
        <div v-if="!data.item.correct">
          <div v-html="data.item.feedback" />
        </div>
        <div v-else>
          None
        </div>
      </template>
      <template v-slot:cell(percentScore)="data">
        {{ data.item.percentScore }}%
      </template>
      <template v-slot:cell(points)="data">
        {{ ((question.points * data.item.percentScore / 100).toFixed(4) * 1).toString() }}
      </template>
    </b-table>
  </div>
</template>

<script>
export default {
  name: 'MarkerSubmission',
  props: {
    question: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    answeredCorrectly: false,
    message: '',
    originalStudentResponse: [],
    structureToLoad: [],
    isInclusive: false,
    studentResponse: [],
    studentResponseFields: []
  }),
  computed: {
    totalPercentScore () {
      return this.studentResponse.reduce((sum, item) => {
        return sum + (parseFloat(item.percentScore) || 0)
      }, 0)
    }
  },
  mounted () {
    this.$nextTick(() => {
      this.isInclusive = JSON.parse(this.question.qti_json).partialCredit === 'inclusive'
      this.studentResponseFields = [{
        key: 'symbol',
        thStyle: 'width: 150px'
      }]
      if (this.isInclusive) {
        this.studentResponseFields.push({
          key: 'percentScore',
          label: 'Percentage',
          thStyle: 'width: 150px'
        })
      }
      this.studentResponseFields.push('feedback')
      const modal = document.getElementById('modal-submission-accepted')
      if (modal) {
        const dialog = modal.querySelector('.modal-dialog')
        if (dialog) {
          dialog.classList.remove('modal-lg')
          dialog.classList.add('modal-xl')
        }
      }
    })
    const answer = JSON.parse(this.question.qti_answer_json).solutionStructure
    console.error('showing answer')
    console.error(answer)
    this.originalStudentResponse = JSON.parse(this.question.student_response).structure
    console.error('showing original student response')
    console.error(this.originalStudentResponse)
    let studentResponse
    studentResponse = JSON.parse(this.question.student_response).structure
    for (const item of ['atoms', 'bonds']) {
      for (let i = 0; i < answer[item].length; i++) {
        const ansMarked = this.hasMark(answer[item][i])
        studentResponse[item][i].correct = (ansMarked && this.hasMark(studentResponse[item][i])) ||
          (!ansMarked && !studentResponse[item][i].hasOwnProperty('mark'))
        if (!studentResponse[item][i].correct) {
          studentResponse[item][i].shouldHaveBeenMarked = ansMarked && !this.hasMark(studentResponse[item][i])
        }
        studentResponse[item][i].percentScore = studentResponse[item][i].correct ? +answer[item][i].correct : -answer[item][i].incorrect
        this.studentResponse.push(studentResponse[item][i])
        this.originalStudentResponse[item][i].answeredCorrectly = studentResponse[item][i].correct
        this.originalStudentResponse[item][i].shouldHaveBeenMarked = studentResponse[item][i].shouldHaveBeenMarked =  ansMarked
        this.originalStudentResponse[item][i].originalIndex = i
        this.originalStudentResponse[item][i].structuralComponent = studentResponse[item][i].structuralComponent = item.replace('s', '')
         this.originalStudentResponse[item][i].index = studentResponse[item][i].index = i
      }
    }
    this.studentResponse.sort((a, b) => {
      return (a.correct === true) - (b.correct === true)
    })
  },
  methods: {
    hasMark (obj) {
      return obj.mark !== null && obj.mark !== undefined
    },
    initLoadStructure (originalIndex, type) {
      this.$bvModal.show('modal-single-marker-sketcher-viewer')
      this.structureToLoad.atoms = []
      this.structureToLoad.bonds = []
      for (const item of ['atoms', 'bonds']) {
        for (let i = 0; i < this.originalStudentResponse[item].length; i++) {
          let value = JSON.parse(JSON.stringify(this.originalStudentResponse[item][i]))
          console.error(value)
          if (type + 's' === item && originalIndex === i) {
            value.mark = value.answeredCorrectly ? 0 : 1
            if (!value.answeredCorrectly) {
              this.answeredCorrectly = false
              switch (type) {
                case ('atom'):
                  this.message = value.shouldHaveBeenMarked
                    ? `You didn't mark the ${value.symbol} atom but should have marked it.`
                    : `You marked the ${value.symbol} atom but should not have marked it.`
                  break
                case ('bond'):
                  this.message = value.shouldHaveBeenMarked ? 'You didn\'t mark the bond but should have marked it.'
                    : 'You marked the bond but should not have marked it.'
              }
            } else {
              this.answeredCorrectly = true
              switch (type) {
                case ('atom'):
                  this.message = value.shouldHaveBeenMarked
                    ? `You correctly marked the ${value.symbol} atom.`
                    : `You correctly didn't mark the ${value.symbol} atom.`
                  break
                case ('bond'):
                  this.message = value.shouldHaveBeenMarked
                    ? 'You correctly marked the bond.'
                    : 'You correctly didn\'t mark the bond.'
              }
            }
          }
          if (item !== (type + 's') || originalIndex !== i) {
            delete value.mark
          }
          this.structureToLoad[item].push(value)
        }
      }
    },
    loadStructure () {
      document.getElementById('single-marker-sketcher-viewer').contentWindow.postMessage({
        method: 'load',
        structure: this.structureToLoad
      }, '*')
    }
  }
}
</script>
