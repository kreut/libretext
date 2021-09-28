<template>
  <div>
    Assignment Information:
    <toggle-button
      class="mt-1"
      :width="80"
      :value="assignmentInformationShownInIFrame"
      :sync="true"
      :font-size="14"
      :margin="4"
      :color="$toggleCheckedUnchecked""
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="updateShownInIFrame('assignment',!assignmentInformationShownInIFrame)"
    />
    <b-icon id="assignmentInformation-tooltip"
            v-b-tooltip.hover
            class="text-muted"
            icon="question-circle"
    />
    <b-tooltip target="assignmentInformation-tooltip" triggers="hover">
      This information includes the name of the assignment, the question number in the assignment, and the time left
      in the assignment.
    </b-tooltip>
    <br>
    Submission Information:
    <toggle-button
      class="mt-1"
      :width="80"
      :value="submissionInformationShownInIFrame"
      :sync="true"
      :font-size="14"
      :margin="4"
      :color="$toggleCheckedUnchecked""
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="updateShownInIFrame('submission',!submissionInformationShownInIFrame)"
    />
    <b-icon id="submissionInformation-tooltip"
            v-b-tooltip.hover
            class="text-muted"
            icon="question-circle"
    />
    <b-tooltip target="submissionInformation-tooltip" triggers="hover">
      The submission information includes when the question was submitted, the score on the question, and the last
      submitted. Please note that for open-ended questions with file uploads, this information will always be shown
      so the
      student can verify that their uploads were successful.
    </b-tooltip>
    <br>
    Attribution:
    <toggle-button
      :width="80"
      class="mt-1"
      :value="attributionInformationShownInIFrame"
      :sync="true"
      :font-size="14"
      :margin="4"
      :color="$toggleCheckedUnchecked""
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="updateShownInIFrame('attribution',!attributionInformationShownInIFrame)"
    />
    <b-icon id="attribution-tooltip"
            v-b-tooltip.hover
            class="text-muted"
            icon="question-circle"
    />
    <b-tooltip target="attribution-tooltip" triggers="hover">
      The attribution includes who authored the question and the license associated with the question.
    </b-tooltip>
  </div>
</template>

<script>
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  name: 'IframeInformation',
  components: { ToggleButton },
  props: {
    assignmentInformationShownInIFrame:
      {
        type: Boolean,
        default: false
      },
    submissionInformationShownInIFrame:
      {
        type: Boolean,
        default: false
      },
    attributionInformationShownInIFrame:
      {
        type: Boolean,
        default: false
      },
    parentUpdateShownInIFrame:
      {
        type: Function,
        default: function () {
        }
      }
  },
  methods: {
    updateShownInIFrame (item, newValue) {
      this.parentUpdateShownInIFrame(item, newValue)
    }
  }
}
</script>

<style scoped>

</style>
