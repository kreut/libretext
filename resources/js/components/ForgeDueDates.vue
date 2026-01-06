<template>
  <div>
    <b-button variant="outline-primary" size="sm" @click="$bvModal.show('modal-due-dates')">
      View Due Dates
    </b-button>

    <b-modal
      id="modal-due-dates"
      :title="`Due Dates for ${questionTitle}`"
      size="lg"
    >
      <b-table
        :items="dueDatesRows"
        :fields="fields"
        striped
        hover
        responsive
      />

      <template #modal-footer>
        <b-button
          size="sm"
          variant="secondary"
          @click="$bvModal.hide('modal-due-dates')"
        >
          Close
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ForgeDueDates',
  data: () => ({
    assignTos: {},
    fields: [
      { key: 'description', label: 'Description' },
      { key: 'date', label: 'Date' }
    ]
  }),
  computed: {
    dueDatesRows () {
      if (!this.assignTos) return []
      return [
        { description: 'Available From', date: this.assignTos.available_from },
        { description: 'Due Date', date: this.assignTos.due }
      ]
    }
  },
  props: {
    assignmentId: {
      type: Number,
      default: 0
    },
    parentQuestionId: {
      type: Number,
      default: 0
    },
    currentDraftQuestionId: {
      type: Number,
      default: 0
    },
    questionTitle: {
      type: String,
      default: ''
    }
  },
  mounted () {
    this.getDueDates()
  },
  methods: {
    async getDueDates () {
      try {
        const { data } = await axios.get(`/api/forge/assignments/${this.assignmentId}/questions/${this.parentQuestionId}/current-question/${this.currentDraftQuestionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.assignTos = data.assign_tos
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
