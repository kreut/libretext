<template>
  <span>
    <b-tooltip :target="`passback-grades-${assignment.id}`"
               delay="750"
               triggers="hover"
               class="text-w"
    >
      Passback grades from {{ assignment.name }} to your LMS.<br><br>
      <span v-if="assignment.num_to_passback>0">There are currently {{ assignment.num_to_passback }} grades
        which still need to be passed back.</span>
      <span v-if="assignment.num_to_passback === 0">There are no grades which need to be passed back.</span>
    </b-tooltip>
    <a :id="`passback-grades-${assignment.id}`"
       href="#"
       @click.prevent="passbackGradesByAssignmentId"
    >
      <font-awesome-icon
        :class="assignment.num_to_passback > 0 ? 'text-warning' : 'text-muted'"
        :icon="paperPlaneIcon"
      />
    </a>
  </span>
</template>

<script>
import axios from 'axios'
import { faPaperPlane } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  name: 'LMSGradePassback',
  components: { FontAwesomeIcon },
  props: {
    assignment: {
      type: Object,
      default: function () {
        return { name: '', id: 0, num_to_passback: 0 }
      }
    }
  },
  data: () => ({
    paperPlaneIcon: faPaperPlane
  }),
  methods: {
    async passbackGradesByAssignmentId () {
      try {
        const { data } = await axios.post(`/api/passback-by-assignment/${this.assignment.id}`)
        this.$noty[data.type](data.message, { timeout: 7000 })
      } catch (error) {
        this.$noty.error(error.message)
      }
    }

  }
}
</script>
