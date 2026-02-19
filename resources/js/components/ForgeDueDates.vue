<template>
  <div>
    <span v-if="colorCodedDueDate && assignTos.due" :class="dueDateClass">
  {{ formatDate(assignTos.due) }}
</span>

    <template v-else-if="!colorCodedDueDate">
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
    </template>
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
        { description: 'Available From', date: this.formatDate(this.assignTos.available_from) },
        { description: 'Due Date', date: this.formatDate(this.assignTos.due) }
      ]
    },
    dueDateClass () {
      if (!this.assignTos.due || !this.assignTos.available_from) return 'dark-red'
      const now = new Date()
      const availableFrom = new Date(this.assignTos.available_from.replace(' at ', ' '))
      const due = new Date(this.assignTos.due.replace(' at ', ' '))
      if (now < availableFrom) return ''
      if (now <= due) return 'text-success'
      if (this.assignTos.final_submission_deadline) {
        const finalDeadline = new Date(this.assignTos.final_submission_deadline.replace(' at ', ' '))
        if (now <= finalDeadline) return 'text-warning'
      }
      return 'dark-red'
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
    },
    colorCodedDueDate: {
      type: Boolean,
      default: false
    }
  },
  mounted () {
    this.getDueDates()
  },
  methods: {
    formatDate (dateString) {
      if (!dateString) return ''
      const d = new Date(dateString.replace(' at ', ' '))
      const month = d.getMonth() + 1
      const day = d.getDate()
      const year = String(d.getFullYear()).slice(-2)
      let hours = d.getHours()
      const minutes = String(d.getMinutes()).padStart(2, '0')
      const ampm = hours >= 12 ? 'pm' : 'am'
      hours = hours % 12 || 12
      return `${month}/${day}/${year} ${hours}:${minutes}${ampm}`
    },
    async getDueDates () {
      try {
        const { data } = await axios.get(`/api/forge/assignments/${this.assignmentId}/questions/${this.parentQuestionId}/current-question/${this.currentDraftQuestionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.assignTos = data.assign_tos
        console.error('assign_tos:', JSON.stringify(this.assignTos))
        this.$emit('due-date-loaded', this.assignTos.due)
      } catch (error) {
        this.$noty.error(error.message)
        this.$emit('due-date-loaded', null)
      }
    }
  }
}
</script>
