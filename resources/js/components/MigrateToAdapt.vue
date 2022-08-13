<template>
  <div>
    <b-modal :id="`modal-confirm-migrate-to-adapt-${assignmentId}-${questionId}`"
             title="Migrate to ADAPT"
    >
      <span v-if="!questionId">
        You are about to migrate all of the questions that you own from this assignment to ADAPT.
      </span>
      <span v-if="questionId">You are about to migrate Question {{ questionTitle }}.</span>
      <template #modal-footer="{ ok, cancel }">
         <span v-if="migrating">
          <b-spinner small type="grow"/>
                      Migrating...
                    </span>
        <b-button v-if="!migrating" size="sm"
                  @click="$bvModal.hide(`modal-confirm-migrate-to-adapt-${assignmentId}-${questionId}`)"
        >
          Cancel
        </b-button>
        <b-button v-if="!migrating"
                  size="sm"
                  variant="primary"
                  @click="migrateToAdapt()"
        >
          {{ assignmentId ? 'Migrate Assignment Questions' : 'Migrate Question' }}
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'MigrateToAdapt',
  props: {
    questions: {
      type: Array,
      default: () => []
    },
    questionTitle: {
      type: String,
      default: ''
    },
    questionId: {
      type: Number,
      default: 0
    },
    assignmentId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    migrating: false
  }),
  methods: {
    async migrateToAdapt () {
      this.migrating = true
      let questionsToMigrate = this.questionId
        ? this.questions.filter(question => question.id === this.questionId)
        : this.questions
      let startMessage = this.questionId
        ? 'Migrating 1 question'
        : `Migrating ${questionsToMigrate.length} questions.`
      this.$bvModal.hide(`modal-confirm-migrate-to-adapt-${this.assignmentId}-${this.questionId}`)
      this.$noty.info(startMessage)
      let numSuccess = 0
      let numErrors = 0
      for (let i = 0; i < questionsToMigrate.length; i++) {
        try {
          const { data } = await axios.post(`/api/libretexts/migrate`, {
            question_id: questionsToMigrate[i].id,
            assignment_id: this.assignmentId
          })
          if (data.type === 'error') {
            numErrors++
            if (data.message) {
              this.$noty[data.type](data.message)
              this.migrating = false
              return false
            }
          } else {
            numSuccess++
          }
          this.$emit('updateMigrationMessage', questionsToMigrate[i].id, data.type, data.question_message)
        } catch (error) {
          numErrors++
          this.$noty.error(error.message)
        }
      }
      this.migrating = false
      this.$noty.info(`Migration complete with ${numSuccess} successes and ${numErrors} errors.`)
    }
  }
}
</script>

