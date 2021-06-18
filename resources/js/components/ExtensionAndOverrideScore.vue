<template>
  <div>
    <b-modal
      id="modal-student-extension-and-override"
      ref="modal"
      :title="`Update Extension And Override for ${studentName}`"
      size="lg"
    >
      <p>Please use this form to either provide an extension for your student or an override score.</p>
      <b-alert variant="info" :show="extensionWarning !== ''">
        <span class="font-weight-bold">{{ extensionWarning }}</span>
      </b-alert>
      <p><span class="font-italic">This assignment was originally due at {{ originalDueDateTime }}.</span></p>
      <b-form ref="form" @submit="submitUpdateExtensionOrOverrideByStudent">
        <b-form-group
          id="extension"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Extension"
          label-for="Extension"
        >
          <b-form-row>
            <b-col lg="7">
              <b-form-datepicker
                v-model="form.extension_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('extension_date') }"
                @shown="form.errors.clear('extension_date')"
              />
              <has-error :form="form" field="extension_date"/>
            </b-col>
            <b-col>
              <b-form-timepicker v-model="form.extension_time"
                                 locale="en"
                                 :class="{ 'is-invalid': form.errors.has('extension_time') }"
                                 @shown="form.errors.clear('extension_time')"
              />
              <has-error :form="form" field="extension_time"/>
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          id="score"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Override Score"
          label-for="Override Score"
        >
          <b-form-row>
            <b-col lg="3">
              <b-form-input
                id="score"
                v-model="form.score"
                type="text"
                placeholder=""
                :class="{ 'is-invalid': form.errors.has('score') }"
                @keydown="form.errors.clear('score')"
              />
              <has-error :form="form" field="score"/>
            </b-col>
          </b-form-row>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="form.errors.clear();$bvModal.hide('modal-student-extension-and-override')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitUpdateExtensionOrOverrideByStudent()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
  </div>

</template>

<script>

export default {
  name: 'ExtensionAndOverrideScore',
  props: {
    assignmentId: { type: Number, default: 0 },
    studentUserId: { type: Number, default: 0 },
    studentName: { type: String, default: '' },
    originalDueDateTime: { type: String, default: '' },
    currentExtensionDate: { type: String, default: '' },
    currentExtensionTime: { type: String, default: '' },
    extensionWarning: { type: String, default: '' },
    form: {
      type: Object,
      default: function () {
      }
    }

  },
  data: () => ({
    min: ''
  }),
  mounted () {
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
  },
  methods:
    {
      async updateScore () {
        try {
          const { data } = await this.form.patch(`/api/scores/${this.assignmentId}/${this.studentUserId}`)
          this.$noty[data.type](data.message)
          return (data.type !== 'error')
        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
          return false
        }
      },
      async updateExtension () {
        try {
          const { data } = await this.form.post(`/api/extensions/${this.assignmentId}/${this.studentUserId}`)
          this.$noty[data.type](data.message)
          return (data.type === 'success')
        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
          return false
        }
      },
      async submitUpdateExtensionOrOverrideByStudent () {
        let isUpdateScore = (this.currentScore !== this.form.score)
        let isUpdateExtension = ((this.currentExtensionDate !== this.form.extension_date) ||
          (this.currentExtensionTime !== this.form.extension_time))

        if (!(isUpdateScore || isUpdateExtension)) {
          this.$noty.error('Please either give an extension or provide an override score.')
        }
        let success = true
        if (isUpdateScore) {
          success = await this.updateScore()
        }
        if (success) {
          if (isUpdateExtension) {
            success = await this.updateExtension()
          }
          if (success) {
            let cellContents = this.form.score === '' ? '-' : this.form.score
            if (this.form.extension_date) {
              cellContents += ' (E)'
            }
            this.$bvModal.hide('modal-student-extension-and-override')
            this.$parent.updateScoreExtension(this.assignmentId, this.studentUserId, cellContents)
          }
        }
      }
    }
}

</script>

<style scoped>

</style>
>>>>>>> Stashed changes
