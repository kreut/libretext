<template>
  <div>
    <div v-if="type !== 'submissionViewer' || showForgeButton" class="d-flex align-items-center">
      <div v-if="!isLoading">
        <div v-if="forgeURL">
          <b-button variant="info" @click="openForgeWindow">
            <HammerIcon/> Open Forge
          </b-button>
        </div>
        <div v-else>
          <b-alert type="warning" show>
            To enable this question please first initially save the Settings.
          </b-alert>
        </div>
      </div>
      <span
        v-if="type === 'submissionViewer'"
        class="ml-3 d-flex align-items-center text-muted"
        style="font-size: 0.85em;"
      >
        <b-button variant="outline-info"
                  size="sm"
                  :disabled="resubmissionEnabled"
                  @click="allowResubmission"
        >
          {{ resubmissionEnabled ? 'Resubmission Enabled' : 'Allow Resubmission' }}
        </b-button>

        <QuestionCircleTooltip id="resubmission-tooltip" class="ml-1"/>

        <b-tooltip
          target="resubmission-tooltip"
          delay="250"
          width="600"
          triggers="hover focus"
          custom-class="custom-tooltip"
        >
          <template v-if="!resubmissionEnabled">
            Allows the student to submit <strong>one additional Forge submission</strong>.<br><br>
            Once enabled, the student may submit again a single time. This action cannot be undone.
          </template>

          <template v-else>
            Resubmission has been enabled for this student.<br><br>
            The student may submit one additional revision. No further resubmissions are allowed.
          </template>
        </b-tooltip>
      </span>
    </div>
    <div v-if="!showForgeButton && type === 'submissionViewer'">
      <b-alert type="info" show>
        There is no Forge submission.
      </b-alert>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import HammerIcon from '../HammerIcon.vue'

export default {
  name: 'ForgeViewer',
  components: { HammerIcon },
  props: {
    showForgeButton: {
      type: Boolean,
      default: true
    },
    resubmissionEnabled: {
      type: Boolean,
      default: false
    },
    type: {
      type: String,
      default: 'questionViewer'
    },
    user: {
      type: Object,
      default: () => {
      }
    },
    studentId: {
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
    isLoading: true,
    forgeURL: '',
    enablingResubmission: false
  }),
  mounted () {
    this.initForge(this.type)
  },
  methods: {
    async openForgeWindow () {
      await this.initForge(this.type)
      window.open(this.forgeURL, '_blank')
    },
    async initForge (type) {
      switch (type) {
        case 'questionViewer':
          try {
            const { data } = await axios.post(
              `/api/forge/assignment/${this.assignmentId}/question/${this.questionId}/initialize`
            )
            if (data.type === 'error') {
              this.$noty.error(data.message)
              return
            }
            if (data.forge_draft_id) {
              this.forgeURL = this.user.role === 2
                ? `${data.domain}/classdetail/${data.forge_class_id}`
                : `${data.domain}/assignment/${data.forge_question_id}?draftId=${data.forge_draft_id}&userId=${data.token}`
            }
          } catch (error) {
            this.$noty.error(error.message)
          }
          break

        case 'submissionViewer':
          try {
            const { data } = await axios.get(`/api/forge/submissions/assignment/${this.assignmentId}/question/${this.questionId}/student/${this.studentId}`)
            if (data.type === 'error') {
              this.$noty.error(data.message)
              return
            }
            if (data.submission_id) {
              this.forgeURL = `${data.domain}/readassignment/${data.submission_id}?draftId=${data.forge_draft_id}`
            }
          } catch (error) {
            this.$noty.error(error.message)
          }
      }
      this.isLoading = false
    },

    async allowResubmission () {
      if (this.resubmissionEnabled || this.enablingResubmission) return

      this.enablingResubmission = true

      try {
        const { data } = await axios.post(
          `/api/forge/submissions/assignment/${this.assignmentId}/question/${this.questionId}/student/${this.studentId}/allow-resubmission`
        )

        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.$emit('updateUploadCount')
        this.$noty.success('Resubmission enabled for this student.')
      } catch (error) {
        this.$noty.error(error.message)
      } finally {
        this.enablingResubmission = false
      }
    }
  }
}
</script>
