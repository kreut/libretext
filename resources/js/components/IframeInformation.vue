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
      :color="toggleColors"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="updateShownInIFrame('assignment',!assignmentInformationShownInIFrame)"
    />
    <QuestionCircleTooltip :id="'assignment-information-tooltip'"/>
    <b-tooltip target="assignment-information-tooltip" delay="250"
               triggers="hover focus"
    >
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
      :color="toggleColors"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="updateShownInIFrame('submission',!submissionInformationShownInIFrame)"
    />
    <QuestionCircleTooltip :id="'submission-information-tooltip'"/>
    <b-tooltip target="submission-information-tooltip" delay="250"
               triggers="hover focus"
    >
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
      :color="toggleColors"
      :labels="{checked: 'Shown', unchecked: 'Hidden'}"
      @change="updateShownInIFrame('attribution',!attributionInformationShownInIFrame)"
    />
    <QuestionCircleTooltip :id="'attribution-tooltip'"/>
    <b-tooltip target="attribution-tooltip" delay="250"
               triggers="hover focus"
    >
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
  data: () => ({
    toggleColors: window.config.toggleColors
  }),
  methods:
    {
      updateShownInIFrame (item, newValue) {
        this.parentUpdateShownInIFrame(item, newValue)
      }
    }
}
</script>

<style scoped>

</style>
