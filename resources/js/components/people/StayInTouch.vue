<style scoped>
  .error {
    animation-name: shakeError;
    animation-fill-mode: forwards;
    animation-duration: .6s;
    animation-timing-function: ease-in-out;
  }

  .form-group-error {
    display: block;
    color:#f57f6c;
  }

  .dashed {
    border-bottom-style: dashed;
  }

  .sit-meta {
    font-size: 0.8rem;
    color: #888;
    margin-top: 2px;
  }

  .unit-select {
    margin-left: 6px;
    padding: 2px 4px;
    border: 1px solid #ccc;
    border-radius: 3px;
    font-size: 0.9rem;
  }

  .start-date-input {
    border: 1px solid #ccc;
    border-radius: 3px;
    padding: 4px 6px;
    font-size: 0.9rem;
    width: 100%;
  }
</style>

<template>
  <div class="di">
    <notifications group="main" position="bottom right" />

    <!-- Not active -->
    <a v-if="!isActive" class="pointer" href="" @click.prevent="showUpdate">
      {{ $t('people.stay_in_touch_modal_title') }}
    </a>

    <!-- Active -->
    <div v-else>
      <div class="di">
        <span
          v-tooltip.bottom="$t('people.stay_in_touch_next_date', { date: formatDate(nextTriggerDate) })"
          class="bb dashed dib pointer nowrap-link"
        >
          {{ frequencyLabel }}
        </span>
        <a class="pointer ml2" href="" @click.prevent="showUpdate">
          {{ $t('app.edit') }}
        </a>
        <a class="pointer ml2" href="" @click.prevent="markAsContacted" :class="{ 'o-50': markingContacted }">
          \u2713 {{ $t('people.stay_in_touch_mark_contacted') }}
        </a>
      </div>
      <div class="sit-meta">
        <span v-if="lastContactedDate">
          {{ $t('people.stay_in_touch_last_contacted', { date: formatDate(lastContactedDate) }) }}
        </span>
        <span v-else class="gray i">
          {{ $t('people.stay_in_touch_never_contacted') }}
        </span>
      </div>
    </div>

    <sweet-modal ref="updateModal" overlay-theme="dark" :title="$t('people.stay_in_touch_modal_title')">
      <div class="tc mw-100">
        <svg viewBox="0 0 423 74" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <defs />
          <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
            <g id="Group-6" transform="translate(2.000000, 2.000000)">
              <g id="Group-3" transform="translate(341.519872, 38.183805) rotate(17.000000) translate(-341.519872, -38.183805) translate(324.519872, 20.183805)">
                <circle id="Oval" stroke="#E5742B" stroke-width="3" cx="17" cy="17" r="17" />
                <path d="M17.0000004,8.5 L17.0000004,17.8978577" id="Path-3" stroke="#E5742B" stroke-width="3" stroke-linecap="round" />
                <circle id="Oval-2" fill="#E5742B" cx="17" cy="23" r="2" />
              </g>
              <g id="Group-4" transform="translate(86.000000, 0.000000)">
                <path d="M238.019857,32.1666667 C241.619977,17.1479167 253.936213,5.64791667 268.936213,3.5" id="Path-2" stroke="#A2B88C" stroke-width="3" stroke-linecap="round" />
                <circle id="Oval" stroke="#A2B88C" stroke-width="3" cx="230" cy="37" r="37" />
                <circle id="Oval" fill="#E5742B" cx="230" cy="37" r="9" />
                <circle id="Oval" stroke="#F5BB27" stroke-width="3" cx="281" cy="3" r="3" />
              </g>
              <path d="M2,35.5 C4.20416667,15.4479167 21.2041667,0.947916667 42,0.947916667 C57,0.947916667 70.1979167,8.94791667 78,20.9479167" id="Path" stroke="#E5742B" stroke-width="3" stroke-linecap="round" />
              <circle id="Oval" stroke="#F5BB27" stroke-width="3" cx="3" cy="38" r="3" />
              <g id="Group-5" transform="translate(54.519872, 53.683805) rotate(17.000000) translate(-54.519872, -53.683805) translate(49.519872, 48.183805)" stroke="#F5BB27" stroke-linecap="square" stroke-width="3">
                <path id="Line" d="M0.277777778,5.5 L9.73854798,5.5" />
                <path id="Line" d="M5.5,10.6764706 L5.5,0.323529412" />
              </g>
              <g id="Group-5" transform="translate(414.000000, 37.500000) scale(-1, -1) translate(-414.000000, -37.500000) translate(409.000000, 32.000000)" stroke="#F5BB27" stroke-linecap="square" stroke-width="3">
                <path id="Line" d="M0.277777778,5.5 L9.73854798,5.5" />
                <path id="Line" d="M5.5,10.6764706 L5.5,0.323529412" />
              </g>
              <g id="Group-5" transform="translate(386.000000, 5.500000) scale(-1, -1) translate(-386.000000, -5.500000) translate(381.000000, 0.000000)" stroke="#A2B88C" stroke-linecap="square" stroke-width="3">
                <path id="Line" d="M0.277777778,5.5 L9.73854798,5.5" />
                <path id="Line" d="M5.5,10.6764706 L5.5,0.323529412" />
              </g>
              <g id="Group-5" transform="translate(386.000000, 64.500000) scale(-1, -1) translate(-386.000000, -64.500000) translate(381.000000, 59.000000)" stroke="#E5742B" stroke-linecap="square" stroke-width="3">
                <path id="Line" d="M0.277777778,5.5 L9.73854798,5.5" />
                <path id="Line" d="M5.5,10.6764706 L5.5,0.323529412" />
              </g>
            </g>
          </g>
        </svg>
      </div>
      <form @submit.prevent="update()">
        <div class="mb4">
          <div v-if="limited" class="mt3 mb3 form-information-message br2">
            <div class="pa3 flex">
              <div class="mr3">
                <svg viewBox="0 0 20 20">
                  <g fill-rule="evenodd">
                    <circle cx="10" cy="10" r="9" fill="currentColor" /><path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m1-5v-3a1 1 0 0 0-1-1H9a1 1 0 1 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2m-1-5.9a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2" />
                  </g>
                </svg>
              </div>
              <div v-html="$t('settings.personalisation_paid_upgrade_vue', {url: 'settings/subscriptions' })"></div>
            </div>
          </div>

          <p class="mt3 b mb3" :class="[ dirltr ? 'tl' : 'tr' ]">
            {{ $t('people.stay_in_touch_modal_desc', { firstname: firstName }) }}
          </p>

          <!-- Toggle + frequency value + unit -->
          <div class="mb3">
            <toggle-button class="mr2" :sync="true" :labels="true" :value="stateInput" @change="stateInput = !stateInput" />
            <div class="dib relative" style="top: -2px;">
              <span class="mr1">{{ $t('people.stay_in_touch_modal_label') }}</span>
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
                <option value="days">{{ $t('people.stay_in_touch_modal_unit_days') }}</option>
                <option value="weeks">{{ $t('people.stay_in_touch_modal_unit_weeks') }}</option>
                <option value="months">{{ $t('people.stay_in_touch_modal_unit_months') }}</option>
              </select>
            </div>
          </div>

          <!-- Start date -->
          <div class="mb3" :class="[ dirltr ? 'tl' : 'tr' ]">
            <label class="db mb1 f6 b">{{ $t('people.stay_in_touch_modal_start_date') }}</label>
            <input
              v-model="startDate"
              type="date"
              class="start-date-input"
            />
            <p class="f7 gray mt1">{{ $t('people.stay_in_touch_modal_start_date_hint') }}</p>
          </div>

          <div v-if="errorMessage !== ''" class="form-error-message mb3">
            <div class="pa2">
              <p class="mb0">
                {{ errorMessage }}
              </p>
            </div>
          </div>
        </div>
      </form>
      <div slot="button" class="tc">
        <a class="btn" href="" @click.prevent="closeModal()">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="update()">
          {{ $t('app.save') }}
        </a>
      </div>
    </sweet-modal>
  </div>
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
      startDate: '',
      nextTriggerDate: null,
      lastContactedDate: null,
      stateInput: false,
      markingContacted: false,
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
        return v === 1 ? 'every week' : `every ${v} weeks`;
      }
      if (this.frequencyUnit === 'months') {
        return v === 1 ? 'every month' : `every ${v} months`;
      }
      const days = this.frequencyInDays;
      return this.$tc('people.stay_in_touch_frequency', days, { count: days });
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
      } else {
        this.frequencyValue = 1;
        this.frequencyUnit = 'days';
      }
    },

    detectUnit(days) {
      if (days % 30 === 0 && days >= 30) return { value: days / 30, unit: 'months' };
      if (days % 7 === 0 && days >= 7) return { value: days / 7, unit: 'weeks' };
      return { value: days, unit: 'days' };
    },

    formatDate(dateAsString) {
      if (!dateAsString) return '';
      const moment = require('moment-timezone');
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

      const payload = {
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

          this.$notify({
            group: 'main',
            title: this.$t('app.default_save_success'),
            text: '',
            width: '500px',
            type: 'success',
          });
        })
        .catch(() => {
          this.errorMessage = this.$t('app.error_save');
        });
    },

    markAsContacted() {
      if (this.markingContacted) return;
      this.markingContacted = true;

      axios.post('people/' + this.hash + '/stayintouch/contacted')
        .then(response => {
          this.lastContactedDate = response.data.last_contacted;
          this.nextTriggerDate = response.data.trigger_date;

          this.$notify({
            group: 'main',
            title: this.$t('people.stay_in_touch_mark_contacted_success'),
            text: '',
            width: '500px',
            type: 'success',
          });
        })
        .catch(() => {
          this.$notify({
            group: 'main',
            title: this.$t('app.error_save'),
            text: '',
            width: '500px',
            type: 'error',
          });
        })
        .finally(() => {
          this.markingContacted = false;
        });
    },

    onFrequencyInput(value) {
      this.stateInput = value > 0;
    },
  },
};
</script>
