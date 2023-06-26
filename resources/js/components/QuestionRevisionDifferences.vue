<template>
  <div>
    <div v-if="!differences.length">
      <b-alert show variant="info">
        No difference between the selected revisions.
      </b-alert>
    </div>
    <div v-if="differences.length" class="mb-2">
      <b-button v-show="!mathJaxRendered"
                size="sm"
                variant="info"
                @click="renderMathJax()"
      >
        Render MathJax
      </b-button>
      <b-button v-show="mathJaxRendered"
                size="sm"
                @click="unrenderMathJax"
      >
        Unrender MathJax
      </b-button>
      <b-button v-show="!diffsShown"
                size="sm"
                variant="info"
                @click="$emit('reloadQuestionRevisionDifferences', mathJaxRendered, true)"
      >
        Show Diffs
      </b-button>
      <b-button v-show="diffsShown"
                size="sm"
                @click="$emit('reloadQuestionRevisionDifferences', mathJaxRendered, false)"
      >
        Hide Diffs
      </b-button>
    </div>
    <table v-if="differences.length" class="table table-striped">
      <thead>
        <tr>
          <th>Property</th>
          <th>
            <span v-if="showCurrentLatestText">Revision in Assignment</span>
            <span v-if="!showCurrentLatestText">Revision {{ revision1.revision_number }}</span>
          </th>
          <th>
            <span v-if="showCurrentLatestText">Latest Revision</span>
            <span v-if="!showCurrentLatestText">Revision {{ revision2.revision_number }}</span>
          </th>
        </tr>
      </thead>
      <tr v-for="(difference,differenceIndex) in differences" :key="`difference-${differenceIndex}`">
        <td>{{ difference.property }}</td>
        <td>
          <div v-html="difference.revision1" />
        </td>
        <td v-show="diffsShown">
          <div v-html="difference.revision2" />
        </td>
        <td v-show="!diffsShown">
          <div v-html="difference.revision2NoDiffs" />
        </td>
      </tr>
    </table>
  </div>
</template>

<script>
import { getRevisionDifferences } from '~/helpers/Revisions'

export default {
  name: 'QuestionRevisionDifferences',
  props: {
    showCurrentLatestText: {
      type: Boolean,
      default: false
    },
    revision1: {
      type: Object,
      default: () => {
      }
    },
    revision2: {
      type: Object,
      default: () => {
      }
    },
    mathJaxRendered: {
      type: Boolean,
      default: false
    },
    diffsShown: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    differences: []
  }),
  mounted () {
    if (this.revision1.id && this.revision1 !== this.revision2) {
      this.differences = this.getRevisionDifferences(this.revision1, this.revision2)
    }
    if (this.mathJaxRendered){
      this.$nextTick(() => {
        MathJax.Hub.Queue(['Typeset', MathJax.Hub])
      })
    }
  },
  methods: {
    getRevisionDifferences,
    renderMathJax () {
      this.$emit('reloadQuestionRevisionDifferences', true, this.diffsShown)
    },
    unrenderMathJax () {
      this.$emit('reloadQuestionRevisionDifferences', false, this.diffsShown)
    }
  }
}
</script>

<style scoped>

</style>
