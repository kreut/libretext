<template>
  <div>
    <b-modal :id="`modal-confirm-migrate-to-adapt-${assignmentId}-${questionId}`"
             title="Migrate to ADAPT"
    >
      <span v-if="assignmentId">
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
      try {
        const { data } = await axios.post(`/api/libretexts/migrate`, {
          question_id: this.questionId,
          assignment_id: this.assignmentId
        })
        let timeout = data.type === 'info' ? 10000 : 4000
        this.$noty[data.type](data.message, { timeout: timeout })
        this.$bvModal.hide(`modal-confirm-migrate-to-adapt-${this.assignmentId}-${this.questionId}`)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.migrating = false
      this.$emit('reloadQuestions')
    }

  }
}
</script>

