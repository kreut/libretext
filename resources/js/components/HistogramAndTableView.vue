<template>
  <div>
    <toggle-button
      :width="105"
      class="mt-2"
      :value="showHistogramView"
      :sync="true"
      size="lg"
      :font-size="14"
      :margin="4"
      :color="toggleColors"
      :labels="{checked: 'Histogram', unchecked: 'Table View'}"
      :aria-label="showHistogramView ? 'Histogram' : 'Table View'"
      @change="showHistogramView = !showHistogramView"
    />
    <div v-if="showHistogramView">
      <scores
              class="border-1 border-info"
              :chartdata="chartdata"
              :height="300" :width="300"
      />
    </div>
    <div v-else>
      <b-table
        striped
        hover
        :no-border-collapse="true"
        :items="chartdata.labelsWithCounts"
        :fields="labelsWithCountsFields"
      />
    </div>
  </div>
</template>

<script>
import Scores from '~/components/Scores'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  components: { Scores, ToggleButton },
  props: {
    chartdata: {
      type: Object,
      default: function () {
        return {}
      }
    },
    height: {
      type: Number,
      default: 0
    },
    width: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    showHistogramView: true,
    toggleColors: window.config.toggleColors,
    labelsWithCountsFields: [
      {
        key: 'number_of_students',
        thClass: 'text-center',
        tdClass: 'text-center'
      },
      {
        key: 'score',
        thClass: 'text-center',
        tdClass: 'text-center'
      }
    ]
  }),
  mounted () {
    this.$forceUpdate()
  }
}
</script>

<style scoped>

</style>
