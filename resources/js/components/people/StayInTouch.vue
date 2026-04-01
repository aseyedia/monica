<style scoped>
  .error {
    animation-name: shakeError;
    animation-fill-mode: forwards;
    animation-duration: .6s;
    animation-timing-function: ease-in-out;
  }
  .form-group-error {
    display: block;
    color: #f57f6c;
  }
  .dashed {
    border-bottom-style: dashed;
  }

  /* Inline button for header row */
  .sit-btn {
    display: inline-block;
    padding: 1px 7px;
    border: 1px solid #b8d4a8;
    border-radius: 12px;
    background: #f0f8ec;
    color: #4a7c3f;
    font-size: 0.8rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
    vertical-align: middle;
    line-height: 1.6;
  }
  .sit-btn:hover {
    background: #e0f0d8;
    border-color: #8cb87c;
  }
  .sit-btn--done {
    border-color: #a8c8d4;
    background: #eef6fa;
    color: #3f6a7c;
  }
  .sit-btn--undo {
    border-color: #d4b8a8;
    background: #faf3ee;
    color: #7c5a3f;
    cursor: pointer;
  }
  .sit-btn--undo:hover {
    background: #f0e4d8;
  }
  .sit-btn--disabled {
    opacity: 0.5;
    pointer-events: none;
  }
  .sit-btn--remove {
    border-color: #d4a8a8;
    background: #faeeee;
    color: #7c3f3f;
    cursor: pointer;
  }
  .sit-btn--remove:hover {
    background: #f0d8d8;
  }

  /* Modal styles */
  .sit-status {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
    text-align: left;
    font-size: 0.9rem;
  }
  .sit-status-row {
    display: flex;
    justify-content: space-between;
    padding: 3px 0;
    color: #555;
  }
  .sit-status-label {
    font-weight: 600;
    color: #333;
  }

  .sit-presets {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
  }
  .sit-preset {
    padding: 4px 14px;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    background: #fff;
    color: #374151;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.15s;
  }
  .sit-preset:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
  }
  .sit-preset--active {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1d4ed8;
  }

  .sit-status-row--preview {
    color: #16a34a;
    font-style: italic;
  }
  .sit-status-row--preview .sit-status-label {
    color: #16a34a;
  }
  .sit-status-row--removed {
    color: #dc2626;
    font-style: italic;
  }
  .sit-status-row--removed .sit-status-label {
    color: #dc2626;
  }

  .sit-form-row {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
  }

  .unit-select {
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    background: #fff;
  }

  .start-date-section {
    text-align: left;
    margin-bottom: 12px;
    padding: 0 8px;
  }
  .start-date-input {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.9rem;
    width: 100%;
  }
</style>

<template>
  <span class="di">
    <notifications group="stay-in-touch" position="bottom right" />

    <!-- Not active: just a link -->
    <a v-if="!isActive" class="pointer" href="" @click.prevent="showUpdate">
      {{ $t('people.stay_in_touch_modal_title') }}
    </a>

    <!-- Active: inline frequency + contacted button -->
    <span v-else class="di">
      <a href="" @click.prevent="showUpdate"
         v-tooltip.bottom="tooltipText"
         class="bb dashed pointer">{{ frequencyLabel }}</a>

      <span v-if="justMarked" class="sit-btn sit-btn--undo ml1" @click.prevent="undoMarkAsContacted">
        done &#8617;
      </span>
      <span v-else class="sit-btn ml1" :class="{ 'sit-btn--disabled': markingContacted }" @click.prevent="markAsContacted">
        &#10003; contacted
      </span>
    </span>

    <!-- Modal -->
    <sweet-modal ref="updateModal" overlay-theme="dark" :title="$t('people.stay_in_touch_modal_title')">

      <!-- Status summary -->
      <div v-if="isActive || stateInput" class="sit-status">
        <div v-if="isActive" class="sit-status-row">
          <span class="sit-status-label">Current</span>
          <span>{{ savedFrequencyLabel }}</span>
        </div>
        <div v-if="isActive" class="sit-status-row">
          <span class="sit-status-label">Next due</span>
          <span>{{ nextTriggerDate ? formatDate(nextTriggerDate) : 'Not set' }}</span>
        </div>
        <div v-if="isDirty && stateInput" class="sit-status-row sit-status-row--preview">
          <span class="sit-status-label">&#10132; New next due</span>
          <span>{{ previewNextDue }}</span>
        </div>
        <div v-if="isActive && !stateInput" class="sit-status-row sit-status-row--removed">
          <span class="sit-status-label">&#10005; Will be removed</span>
          <span></span>
        </div>
        <div class="sit-status-row">
          <span class="sit-status-label">Last contacted</span>
          <span>{{ lastContactedDate ? formatDate(lastContactedDate) : 'Never' }}</span>
        </div>
      </div>

      <p class="mt0 mb3 gray f6 tc">
        {{ $t('people.stay_in_touch_modal_desc', { firstname: firstName }) }}
      </p>

      <!-- Quick presets -->
      <div class="sit-presets">
        <span class="sit-preset" :class="{ 'sit-preset--active': frequencyInDays === 7 }" @click="applyPreset(1, 'weeks')">1 week</span>
        <span class="sit-preset" :class="{ 'sit-preset--active': frequencyInDays === 14 }" @click="applyPreset(2, 'weeks')">2 weeks</span>
        <span class="sit-preset" :class="{ 'sit-preset--active': frequencyInDays === 30 }" @click="applyPreset(1, 'months')">1 month</span>
        <span class="sit-preset" :class="{ 'sit-preset--active': frequencyInDays === 90 }" @click="applyPreset(3, 'months')">3 months</span>
        <span class="sit-preset" :class="{ 'sit-preset--active': frequencyInDays === 180 }" @click="applyPreset(6, 'months')">6 months</span>
      </div>

      <form @submit.prevent="update()">
        <!-- Toggle + custom frequency -->
        <div class="sit-form-row">
          <toggle-button :sync="true" :labels="true" :value="stateInput" @change="stateInput = !stateInput" />
          <span>every</span>
          <form-input
            :id="'frequency'"
            v-model.number="frequencyValue"
            :input-type="'number'"
            :width="55"
            :required="true"
            :validator="$v.frequencyValue"
            @input="onFrequencyInput($event)"
          />
          <select v-model="frequencyUnit" class="unit-select">
            <option value="days">days</option>
            <option value="weeks">weeks</option>
            <option value="months">months</option>
          </select>
        </div>

        <!-- Start date -->
        <div class="start-date-section">
          <label class="db mb1 f7 b gray">{{ $t('people.stay_in_touch_modal_start_date') }}</label>
          <input v-model="startDate" type="date" class="start-date-input" />
          <p class="f7 gray mt1 mb0">{{ $t('people.stay_in_touch_modal_start_date_hint') }}</p>
        </div>

        <div v-if="errorMessage !== ''" class="form-error-message mb3">
          <div class="pa2">
            <p class="mb0">{{ errorMessage }}</p>
          </div>
        </div>
      </form>

      <div slot="button" class="tc">
        <a class="btn" href="" @click.prevent="closeModal()">
          {{ $t('app.cancel') }}
        </a>
        <a v-if="isActive" class="btn sit-btn--remove" href="" @click.prevent="remove()" style="margin-right:8px">
          Remove
        </a>
        <a class="btn btn-primary" href="" @click.prevent="update()">
          {{ $t('app.save') }}
        </a>
      </div>
    </sweet-modal>
  </span>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';
import { ToggleButton } from 'vue-js-toggle-button';
import { validationMixin } from 'vuelidate';
import { required, numeric, minValue } from 'vuelidate/lib/validators';

export default {

  components: {
    SweetModal,
    ToggleButton,
  },

  mixins: [validationMixin],

  props: {
    hash: {
      type: String,
      default: '',
    },
    firstName: {
      type: String,
      default: '',
    },
    frequency: {
      type: Number,
      default: 0,
    },
    triggerDate: {
      type: String,
      default: null,
    },
    lastContacted: {
      type: String,
      default: null,
    },
    limited: {
      type: Boolean,
      default: false,
    },
  },

  validations: {
    frequencyValue: {
      required,
      numeric,
      minValue: minValue(1),
    },
  },

  data() {
    return {
      isActive: false,
      errorMessage: '',
      frequencyValue: 1,
      frequencyUnit: 'days',
      savedFrequencyValue: 1,
      savedFrequencyUnit: 'days',
      startDate: '',
      nextTriggerDate: null,
      lastContactedDate: null,
      stateInput: false,
      markingContacted: false,
      justMarked: false,
      undoTimer: null,
      previousLastContacted: null,
      previousTriggerDate: null,
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    },

    frequencyInDays() {
      const v = parseInt(this.frequencyValue) || 1;
      if (this.frequencyUnit === 'weeks') return v * 7;
      if (this.frequencyUnit === 'months') return v * 30;
      return v;
    },

    frequencyLabel() {
      const v = parseInt(this.frequencyValue) || 1;
      if (this.frequencyUnit === 'weeks') {
        return v === 1 ? 'every week' : 'every ' + v + ' weeks';
      }
      if (this.frequencyUnit === 'months') {
        return v === 1 ? 'every month' : 'every ' + v + ' months';
      }
      const days = this.frequencyInDays;
      return this.$tc('people.stay_in_touch_frequency', days, { count: days });
    },

    savedFrequencyInDays() {
      const v = parseInt(this.savedFrequencyValue) || 1;
      if (this.savedFrequencyUnit === 'weeks') return v * 7;
      if (this.savedFrequencyUnit === 'months') return v * 30;
      return v;
    },

    savedFrequencyLabel() {
      const v = parseInt(this.savedFrequencyValue) || 1;
      if (this.savedFrequencyUnit === 'weeks') {
        return v === 1 ? 'every week' : 'every ' + v + ' weeks';
      }
      if (this.savedFrequencyUnit === 'months') {
        return v === 1 ? 'every month' : 'every ' + v + ' months';
      }
      return this.$tc('people.stay_in_touch_frequency', this.savedFrequencyInDays, { count: this.savedFrequencyInDays });
    },

    isDirty() {
      if (!this.isActive && this.stateInput) return true;
      if (this.isActive && !this.stateInput) return true;
      if (this.frequencyInDays !== this.savedFrequencyInDays) return true;
      if (this.startDate) return true;
      return false;
    },

    previewNextDue() {
      var moment = require('moment-timezone');
      moment.locale(this._i18n.locale);
      moment.tz.setDefault('UTC');
      var base;
      if (this.startDate) {
        base = moment(this.startDate);
      } else if (this.lastContactedDate) {
        base = moment(this.lastContactedDate);
      } else {
        base = moment();
      }
      var next = base.clone().add(this.frequencyInDays, 'days');
      // If the computed date is in the past, roll forward
      while (next.isBefore(moment(), 'day')) {
        next.add(this.frequencyInDays, 'days');
      }
      var date = moment.tz(next, this.$root.timezone);
      return date.format('LL');
    },

    tooltipText() {
      var lines = [];
      if (this.nextTriggerDate) {
        lines.push(this.$t('people.stay_in_touch_next_date', { date: this.formatDate(this.nextTriggerDate) }));
      }
      if (this.lastContactedDate) {
        lines.push(this.$t('people.stay_in_touch_last_contacted', { date: this.formatDate(this.lastContactedDate) }));
      } else {
        lines.push(this.$t('people.stay_in_touch_never_contacted'));
      }
      return lines.join('\n');
    },
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.isActive = (this.frequency > 0);
      this.stateInput = this.isActive;
      this.nextTriggerDate = this.triggerDate;
      this.lastContactedDate = this.lastContacted;

      if (this.frequency > 0) {
        const detected = this.detectUnit(this.frequency);
        this.frequencyValue = detected.value;
        this.frequencyUnit = detected.unit;
        this.savedFrequencyValue = detected.value;
        this.savedFrequencyUnit = detected.unit;
      } else {
        this.frequencyValue = 1;
        this.frequencyUnit = 'days';
        this.savedFrequencyValue = 1;
        this.savedFrequencyUnit = 'days';
      }
    },

    detectUnit(days) {
      if (days % 30 === 0 && days >= 30) return { value: days / 30, unit: 'months' };
      if (days % 7 === 0 && days >= 7) return { value: days / 7, unit: 'weeks' };
      return { value: days, unit: 'days' };
    },

    formatDate(dateAsString) {
      if (!dateAsString) return '';
      var moment = require('moment-timezone');
      moment.locale(this._i18n.locale);
      moment.tz.setDefault('UTC');
      var date = moment.tz(moment(dateAsString), this.$root.timezone);
      return date.format('LL');
    },

    showUpdate() {
      this.errorMessage = '';
      this.$refs.updateModal.open();
    },

    closeModal() {
      this.$refs.updateModal.close();
    },

    applyPreset(value, unit) {
      this.frequencyValue = value;
      this.frequencyUnit = unit;
      this.stateInput = true;
    },

    update() {
      this.errorMessage = '';

      if (this.limited) {
        this.errorMessage = this.$t('people.stay_in_touch_premium');
        return;
      }

      this.$v.$touch();
      if (this.$v.$invalid) {
        return;
      }

      var payload = {
        frequency: this.frequencyInDays,
        state: this.stateInput,
      };

      if (this.startDate) {
        payload.start_date = this.startDate;
      }

      axios.post('people/' + this.hash + '/stayintouch', payload)
        .then(response => {
          this.$refs.updateModal.close();
          this.isActive = this.stateInput;
          this.nextTriggerDate = response.data.trigger_date;
          this.lastContactedDate = response.data.last_contacted;

          // Sync saved state for dirty tracking
          if (this.stateInput) {
            var detected = this.detectUnit(this.frequencyInDays);
            this.frequencyValue = detected.value;
            this.frequencyUnit = detected.unit;
            this.savedFrequencyValue = detected.value;
            this.savedFrequencyUnit = detected.unit;
          }
          this.startDate = '';

          this.$notify({
            group: 'stay-in-touch',
            title: this.$t('app.default_save_success'),
            text: '',
            type: 'success',
          });
        })
        .catch(function() {
          this.errorMessage = this.$t('app.error_save');
        }.bind(this));
    },

    markAsContacted() {
      if (this.markingContacted) return;
      this.markingContacted = true;

      this.previousLastContacted = this.lastContactedDate;
      this.previousTriggerDate = this.nextTriggerDate;

      axios.post('people/' + this.hash + '/stayintouch/contacted')
        .then(response => {
          this.lastContactedDate = response.data.last_contacted;
          this.nextTriggerDate = response.data.trigger_date;

          this.justMarked = true;
          if (this.undoTimer) clearTimeout(this.undoTimer);
          this.undoTimer = setTimeout(function() {
            this.justMarked = false;
          }.bind(this), 6000);
        })
        .catch(function() {
          this.$notify({
            group: 'stay-in-touch',
            title: this.$t('app.error_save'),
            text: '',
            type: 'error',
          });
        }.bind(this))
        .finally(function() {
          this.markingContacted = false;
        }.bind(this));
    },

    undoMarkAsContacted() {
      if (this.undoTimer) clearTimeout(this.undoTimer);
      this.justMarked = false;

      var payload = {
        frequency: this.frequencyInDays,
        state: true,
      };

      // Restore previous trigger date by recalculating from previous last-contacted
      if (this.previousLastContacted) {
        payload.start_date = this.previousLastContacted;
      }

      axios.post('people/' + this.hash + '/stayintouch', payload)
        .then(response => {
          this.nextTriggerDate = response.data.trigger_date;
          this.lastContactedDate = this.previousLastContacted;
        })
        .catch(function() {
          this.$notify({
            group: 'stay-in-touch',
            title: this.$t('app.error_save'),
            text: '',
            type: 'error',
          });
        }.bind(this));
    },

    remove() {
      this.stateInput = false;
      this.update();
    },

    onFrequencyInput(value) {
      this.stateInput = value > 0;
    },
  },
};
</script>
