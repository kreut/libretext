<template>
  <div>
    <div v-if="thingToHide">
      <b-modal :id="`modal-remove-auto-release-${assignment.id}`"
               :title="`Deactivate Auto-Release for ${assignment.name}`"
               no-close-on-esc
      >
        <p>
          You are about to manually hide your {{ thingToHide }}. {{ autoReleaseTimingMessage }}
        </p>
        <p>
          In addition to hiding your {{ thingToHide }} would you like to deactivate this auto-release?
        </p>
        <b-form-radio-group
          id="remove-auto-release"
          v-model="deactivateAutoRelease"
          class="mt-2"
          stacked
          label="Deactivate Auto-Release"
        >
          <b-form-radio value="1">
            Yes
          </b-form-radio>
          <b-form-radio value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide(`modal-remove-auto-release-${assignment.id}`)"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleSubmitShowHideAssignmentProperty()"
          >
            Submit
          </b-button>
        </template>
      </b-modal>
    </div>
    <b-modal :id="`modal-show-assignment-statistics-${assignment.id}`"
             title="Show Assignment Statistics"
    >
      <p>
        It looks like you want to show the assignment statistics but your students can't view their own scores.
        Are you sure that you want to show the assignment statistics?
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`modal-show-assignment-statistics-${assignment.id}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitShowHideAssignmentProperty('students_can_view_assignment_statistics')"
        >
          Yes, I want to show the assignment statistics.
        </b-button>
      </template>
    </b-modal>

    <b-modal :id="`modal-hide-scores-${assignment.id}`"
             title="Hide Scores"
    >
      <p>
        This is a "<strong>{{ assessmentType }}</strong>" assignment. Typically
        for this type of assignment, students get immediate feedback.
        Are you sure that you want to hide the scores?
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`modal-hide-scores-${assignment.id}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitShowHideAssignmentProperty('show_scores')"
        >
          Yes, I want to hide the scores!
        </b-button>
      </template>
    </b-modal>

    <b-modal :id="`modal-hide-solutions-${assignment.id}`"
             title="Show Solutions"
    >
      <p>
        This is a "<strong>{{ assessmentType }}</strong>" assignment. By releasing the solutions, all students will be
        able to see them immediately,
        even if you chose "Automatic" as your option for Solutions Availability (see the Properties tab). Are you sure
        you want to show the solutions?
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide(`modal-hide-solutions-${assignment.id}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitShowHideAssignmentProperty('solutions_released')"
        >
          Yes, I want to show the solutions!
        </b-button>
      </template>
    </b-modal>
    <div v-show="isMe">
      <b-form-checkbox
        :id="`${property}-${assignment.id}`"
        :key="property"
        v-model="assignment[property]"
        :name="`${property}`"
        value="1"
        unchecked-value="0"
        class="custom-checkbox"
        @change="initShowHideAssignmentProperty()"
      >
        Shown
      </b-form-checkbox>
      <b-form-checkbox
        :id="`auto-release-activated-${property}-${assignment.id}`"
        :key="`auto-release-activated-${property}`"
        v-model="assignment['auto_release_activated_' + property]"
        :name="`auto-release-activated-${property}`"
        value="1"
        :disabled="disabled"
        unchecked-value="0"
        @change="updateAutoReleaseActivated()"
      >
        Auto-release
      <QuestionCircleTooltip v-if="disabled" :id="`disabled-${assignment.id}-${property}-tooltip`" />

      <b-tooltip :target="`disabled-${assignment.id}-${property}-tooltip`"
                 delay="250"
                 triggers="hover focus"
      >
        {{ getDisabledMessage(assignment) }}
      </b-tooltip>
      </b-form-checkbox>
    </div>
    <div v-show="!showAutoRelease">
      <toggle-button
        tabindex="0"
        :width="84"
        :value="Boolean(assignment[property])"
        :sync="true"
        :font-size="14"
        :color="toggleColors"
        :labels="{checked: 'Shown', unchecked: 'Hidden'}"
        @change="initShowHideAssignmentProperty()"
      />
    </div>
  </div>
</template>

<script>
import 'vue-loading-overlay/dist/vue-loading.css'
import { ToggleButton } from 'vue-js-toggle-button'
import axios from 'axios'

export default {
  name: 'ShowHideAssignmentProperties',
  components: { ToggleButton },
  props: {
    assignment:
      {
        type: Object,
        default: function () {
        }
      },
    property: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    showAutoRelease: false,
    disabled: false,
    isMe: window.config.isMe,
    toggleColors: window.config.toggleColors,
    assessmentType: '',
    deactivateAutoRelease: 1,
    thingToHide: '',
    autoReleaseTimingMessage: ''
  }),
  mounted () {
    this.showAutoRelease = this.isMe
    this.assessmentType = this.assignment.assessment_type
    this.disabled = this.assignment.assessment_type === 'clicker' || !this.assignment['auto_release_' + this.property]
  },
  methods: {
    getDisabledMessage (assignment) {
      const clicker = assignment.assessment_type === 'clicker'
      const noProperty = !assignment['auto_release_' + this.property]
      let
        formattedProperty
      switch (this.property) {
        case ('shown'):
          formattedProperty = '\'Assignment\''
          break
        case ('show_scores'):
          formattedProperty = '\'Scores\''
          break
        case ('solutions_released'):
          formattedProperty = '\'Solutions\''
          break
        case ('students_can_view_assignment_statistics'):
          formattedProperty = '\'Statistics\''
          break
      }
      const noPropertyMessage = noProperty ? `${formattedProperty} is not set for auto-release in your assignment properties.` : ''
      let message
      message = ''
      if (clicker) {
        message = 'Auto-release is not available for clicker assignments.  '
      } else if (noProperty) {
        message += noPropertyMessage
      }
      return message
    },

    async updateAutoReleaseActivated () {
      this.assignment['auto_release_activated_' + this.property] = !this.assignment['auto_release_activated_' + this.property]
      try {
        const { data } = await axios.patch(`/api/auto-release/activated/${this.assignment.id}`, {
          property: this.property
        })
        if (data.type === 'error') {
          this.assignment['auto_release_activated_' + this.property] = !this.assignment['auto_release_activated_' + this.property]
        } else {
          data.message += this.addAutoReleaseMessage(this.property)
        }
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
        this.assignment['auto_release_activated_' + this.property] = !this.assignment['auto_release_activated_' + this.property]
      }
    },
    async initShowHideAssignmentProperty () {
      if (this.assignment[this.property] &&
        this.assignment[`auto_release_${this.property}`] &&
        this.assignment[`auto_release_activated_${this.property}`]) {
        this.deactivateAutoRelease = 1
        switch (this.property) {
          case ('shown'):
            this.thingToHide = 'assignment'
            break
          case ('solutions_released'):
            this.thingToHide = 'solutions'
            break
          case ('show_scores'):
            this.thingToHide = 'scores'
            break
          case ('students_can_view_assignment_statistics'):
            this.thingToHide = 'statistics'
            break
        }
        const { data } = await axios.get(`/api/auto-release/assignment/${this.assignment.id}/property/${this.property}/timing-message`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.autoReleaseTimingMessage = data.timing_message
        this.$nextTick(() => {
          this.$bvModal.show(`modal-remove-auto-release-${this.assignment.id}`)
        })
      } else {
        this.deactivateAutoRelease = 0
        await this.handleSubmitShowHideAssignmentProperty()
      }
    },
    async handleSubmitShowHideAssignmentProperty () {
      switch (this.property) {
        case ('shown'):
          await this.submitShowHideAssignmentProperty('shown')
          break
        case ('solutions_released'):
          this.assessmentType === 'real time' && !this.assignment.solutions_released && this.assignment.solutions_availability === 'automatic'
            ? this.$bvModal.show(`modal-hide-solutions-${this.assignment.id}`)
            : await this.submitShowHideAssignmentProperty('solutions_released')
          break
        case ('show_scores'):
          this.assessmentType !== 'delayed' && Boolean(this.assignment.show_scores)
            ? this.$bvModal.show(`modal-hide-scores-${this.assignment.id}`)
            : await this.submitShowHideAssignmentProperty('show_scores')
          break
        case ('students_can_view_assignment_statistics'):
          !this.assignment.students_can_view_assignment_statistics && !this.assignment.show_scores
            ? this.$bvModal.show(`modal-show-assignment-statistics-${this.assignment.id}`)
            : await this.submitShowHideAssignmentProperty('students_can_view_assignment_statistics')
          break
      }
    },
    addAutoReleaseMessage (property) {
      let message
      message = ''
      if (Boolean(this.assignment[property]) && this.assignment['auto_release_activated_' + property]) {
        message += '<br><br>Note that since this property is manually shown, auto-release has no effect.'
      }
      return message
    },
    async submitShowHideAssignmentProperty (property) {
      let url = ''
      let initModal = ''
      try {
        switch (property) {
          case ('shown'):
            url = `/api/assignments/${this.assignment.id}/show-assignment`
            break
          case ('solutions_released'):
            url = `/api/assignments/${this.assignment.id}/solutions-released`
            initModal = `modal-hide-solutions-${this.assignment.id}`
            break
          case ('show_scores'):
            url = `/api/assignments/${this.assignment.id}/show-scores`
            initModal = `modal-hide-scores-${this.assignment.id}`
            break
          case ('students_can_view_assignment_statistics'):
            url = `/api/assignments/${this.assignment.id}/show-assignment-statistics`
            initModal = `modal-show-assignment-statistics-${this.assignment.id}`
            break
          default:
            alert(`${property} is not a valid toggle property.`)
        }
        this.assignment[property] = !this.assignment[property]
        if (this.deactivateAutoRelease) {
          this.assignment['auto_release_activated_' + property] = '0'
        }
        const { data } = await axios.patch(url, { deactivate_auto_release: this.deactivateAutoRelease })
        if (data.type === 'success') {
          data.message += this.addAutoReleaseMessage(property)
        }
        this.$noty[data.type](data.message)

        if (data.type === 'error') {
          this.$bvModal.hide(initModal)
          this.$bvModal.hide(`modal-remove-auto-release-${this.assignment.id}`)
          this.assignment[property] = !this.assignment[property]
          if (this.deactivateAutoRelease) {
            this.assignment['auto_release_activated_' + property] = '1'
          }
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(initModal)
      this.$bvModal.hide(`modal-remove-auto-release-${this.assignment.id}`)
      this.$emit('refreshPage')
    }
  }
}
</script>
