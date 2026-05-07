<template>
  <div>
    <table class="table table-striped">
      <thead>
      <tr>
        <th>Correct Value</th>
        <th>Tolerance Type</th>
        <th>Tolerance</th>
        <th>Accepted Range</th>
      </tr>
      </thead>
      <tbody>
      <tr>
        <td>
          <b-form-input
            v-model="qtiJson.correctResponse.value"
            type="text"
            size="sm"
            style="width:100px"
            :class="{ 'is-invalid': questionForm.errors.has('correct_response') }"
            @keydown="questionForm.errors.clear('correct_response')"
          />
          <has-error :form="questionForm" field="correct_response" />
        </td>
        <td>
          <b-form-radio-group
            v-model="qtiJson.correctResponse.toleranceType"
            :options="toleranceTypeOptions"
            name="single-numerical-tolerance-type"
            @change="onToleranceTypeChange"
          />
        </td>
        <td>
          <b-input-group
            v-if="qtiJson.correctResponse.toleranceType !== 'relative'"
            append="±"
            size="sm"
            style="width:110px"
          >
            <b-form-input
              v-model="qtiJson.correctResponse.marginOfError"
              type="text"
              :class="{ 'is-invalid': questionForm.errors.has('margin_of_error') }"
              @keydown="questionForm.errors.clear('margin_of_error')"
            />
          </b-input-group>
          <b-input-group
            v-if="qtiJson.correctResponse.toleranceType === 'relative'"
            append="%"
            size="sm"
            style="width:110px"
          >
            <b-form-input
              v-model="qtiJson.correctResponse.relativeTolerance"
              type="text"
              :class="{ 'is-invalid': questionForm.errors.has('relative_tolerance') }"
              @keydown="questionForm.errors.clear('relative_tolerance')"
            />
          </b-input-group>
          <has-error :form="questionForm" field="margin_of_error" />
          <has-error :form="questionForm" field="relative_tolerance" />
        </td>
        <td class="align-middle text-muted small">
          {{ rangePreview }}
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'SingleNumerical',
  props: {
    qtiJson: {
      type: Object,
      default: () => {}
    },
    questionForm: {
      type: Object,
      default: () => {}
    }
  },
  data () {
    return {
      toleranceTypeOptions: [
        { text: 'Absolute (±)', value: 'absolute' },
        { text: 'Relative (%)', value: 'relative' }
      ]
    }
  },
  computed: {
    rangePreview () {
      const val = parseFloat(this.qtiJson.correctResponse.value)
      if (isNaN(val)) return '—'

      if (this.qtiJson.correctResponse.toleranceType === 'relative') {
        const pct = parseFloat(this.qtiJson.correctResponse.relativeTolerance)
        if (isNaN(pct) || pct < 0) return '—'
        if (pct === 0) return `Exact: ${val}`
        const tol = Math.abs(val) * pct / 100
        return `${+(val - tol).toFixed(6)} to ${+(val + tol).toFixed(6)} (±${pct}%)`
      } else {
        const tol = parseFloat(this.qtiJson.correctResponse.marginOfError)
        if (isNaN(tol) || tol < 0) return '—'
        if (tol === 0) return `Exact: ${val}`
        return `${+(val - tol).toFixed(6)} to ${+(val + tol).toFixed(6)}`
      }
    }
  },
  created () {
    // Backwards compat: set toleranceType if missing
    if (!this.qtiJson.correctResponse.toleranceType) {
      this.$set(this.qtiJson.correctResponse, 'toleranceType', 'absolute')
    }
    if (!this.qtiJson.correctResponse.relativeTolerance) {
      this.$set(this.qtiJson.correctResponse, 'relativeTolerance', '0')
    }
  },
  methods: {
    onToleranceTypeChange (type) {
      if (type === 'relative') {
        this.qtiJson.correctResponse.marginOfError = '0'
      } else {
        this.qtiJson.correctResponse.relativeTolerance = '0'
      }
    }
  }
}
</script>
