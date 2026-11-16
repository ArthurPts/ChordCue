import './bootstrap';

import Alpine from 'alpinejs';
import { AudioEngine } from './audio/audio-engine';
import { chordsheetPlayer } from './player';
import { simpleEditor } from './simple-editor';

window.AudioEngine = AudioEngine;
window.Alpine = Alpine;

Alpine.data('chordsheetPlayer', chordsheetPlayer);
Alpine.data('simpleEditor', simpleEditor);

Alpine.start();
