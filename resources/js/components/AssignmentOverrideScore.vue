<template>
  <div>
    <b-modal
      id="modal-student-override-score"
      :key="`${assignmentId}-${studentUserId}`"
      :title="`Update Score for ${studentName} on ${assignmentName}`"
    >
      <b-form ref="form">
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
          @click="form.errors.clear();$bvModal.hide('modal-student-override-score')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="updateScore()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>

export default {
  name: 'AssignmentOverrideScore',
  props: {
    assignmentId: { type: Number, default: 0 },
    assignmentName: { type: String, default: '' },
    studentUserId: { type: Number, default: 0 },
    studentName: { type: String, default: '' },
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
        if (this.currentScore === this.form.score) {
          this.$noty.error('Please provide an override score.')
        }
        try {
          const { data } = await this.form.patch(`/api/scores/${this.assignmentId}/${this.studentUserId}`)
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            let cellContents = this.form.score === '' ? '-' : this.form.score
            if (this.form.extension_date) {
              cellContents += ' (E)'
            }
            this.$bvModal.hide('modal-student-override-score')
            this.$parent.updateScore(this.assignmentId, this.studentUserId, cellContents)
          }
        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
          return false
        }
      }
    }
}

</script>

