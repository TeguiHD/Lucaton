(function () {
  function ready(callback) {
    if (document.readyState !== 'loading') {
      callback();
    } else {
      document.addEventListener('DOMContentLoaded', callback);
    }
  }

  function toggleEditingState(form, isEditing) {
    var editButton = form.querySelector('[data-profile-action="edit"]');
    var actions = form.querySelector('[data-profile-actions]');
    var editors = form.querySelectorAll('[data-profile-editor]');
    var displays = form.querySelectorAll('[data-profile-display]');
    var inputs = form.querySelectorAll('[data-profile-input]');

    inputs.forEach(function (input) {
      input.disabled = !isEditing;
    });

    editors.forEach(function (editor) {
      editor.classList.toggle('hidden', !isEditing);
    });

    displays.forEach(function (display) {
      display.classList.toggle('hidden', isEditing);
    });

    if (actions) {
      actions.classList.toggle('hidden', !isEditing);
    }

    if (editButton) {
      editButton.classList.toggle('hidden', isEditing);
    }

    form.classList.toggle('is-editing', isEditing);
  }

  ready(function () {
    var editForms = document.querySelectorAll('[data-profile-edit]');

    editForms.forEach(function (form) {
      var inputs = form.querySelectorAll('[data-profile-input]');
      var editButton = form.querySelector('[data-profile-action="edit"]');
      var cancelButton = form.querySelector('[data-profile-action="cancel"]');

      inputs.forEach(function (input) {
        if (!input.hasAttribute('data-original-value')) {
          input.setAttribute('data-original-value', input.value);
        }
        input.disabled = true;
      });

      if (editButton) {
        editButton.addEventListener('click', function () {
          toggleEditingState(form, true);
          var firstInput = inputs.length ? inputs[0] : null;
          if (firstInput) {
            firstInput.focus({ preventScroll: true });
            if (typeof firstInput.setSelectionRange === 'function') {
              var len = firstInput.value.length;
              firstInput.setSelectionRange(len, len);
            }
          }
        });
      }

      if (cancelButton) {
        cancelButton.addEventListener('click', function () {
          inputs.forEach(function (input) {
            var original = input.getAttribute('data-original-value') || '';
            if (input.value !== original) {
              input.value = original;
            }
          });
          toggleEditingState(form, false);
        });
      }

      form.addEventListener('submit', function () {
        inputs.forEach(function (input) {
          input.disabled = false;
        });
      });
    });

    var avatarForm = document.querySelector('[data-profile-avatar]');
    if (avatarForm) {
      var fileInput = avatarForm.querySelector('[data-profile-avatar-input]');
      var triggerButton = avatarForm.querySelector('[data-profile-avatar-trigger]');
      var saveButton = avatarForm.querySelector('[data-profile-avatar-save]');
      var cancelButton = avatarForm.querySelector('[data-profile-avatar-cancel]');
      var resetButton = avatarForm.querySelector('[data-profile-avatar-reset="true"]');
      var fileName = avatarForm.querySelector('[data-profile-avatar-filename]');
      var preview = avatarForm.querySelector('[data-profile-avatar-preview]');
      var originalSrc = preview ? preview.getAttribute('data-original-src') : '';
      var lastSubmitter = null;

      avatarForm.querySelectorAll('button[type="submit"]').forEach(function (button) {
        button.addEventListener('click', function (event) {
          lastSubmitter = event.currentTarget;
        });
      });

      function resetAvatarState() {
        if (fileName) {
          fileName.textContent = '';
          fileName.classList.add('hidden');
        }
        if (saveButton) {
          saveButton.classList.add('hidden');
        }
        if (cancelButton) {
          cancelButton.classList.add('hidden');
        }
        if (fileInput) {
          fileInput.value = '';
        }
        if (preview && originalSrc) {
          preview.src = originalSrc;
        }
      }

      if (triggerButton && fileInput) {
        triggerButton.addEventListener('click', function () {
          fileInput.click();
        });
      }

      if (fileInput) {
        fileInput.addEventListener('change', function () {
          var file = fileInput.files && fileInput.files[0];
          if (file) {
            if (fileName) {
              fileName.textContent = file.name;
              fileName.classList.remove('hidden');
            }
            if (saveButton) {
              saveButton.classList.remove('hidden');
            }
            if (cancelButton) {
              cancelButton.classList.remove('hidden');
            }
            if (preview) {
              var objectUrl = URL.createObjectURL(file);
              preview.src = objectUrl;
              preview.onload = function () {
                URL.revokeObjectURL(objectUrl);
              };
            }
          } else {
            resetAvatarState();
          }
        });
      }

      if (cancelButton) {
        cancelButton.addEventListener('click', function () {
          resetAvatarState();
        });
      }

      avatarForm.addEventListener('submit', function (event) {
        var submitter = event.submitter || lastSubmitter;
        if (submitter && submitter.getAttribute('data-profile-avatar-reset') === 'true') {
          resetAvatarState();
          return;
        }

        if (!fileInput || !(fileInput.files && fileInput.files[0])) {
          event.preventDefault();
          if (triggerButton) {
            triggerButton.classList.add('ring-2', 'ring-copihue-500', 'ring-offset-2');
            setTimeout(function () {
              triggerButton.classList.remove('ring-2', 'ring-copihue-500', 'ring-offset-2');
            }, 1500);
          }
        }
      });
    }
  });
})();
