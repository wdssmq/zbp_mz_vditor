/* global Vditor, contentBarBtn, contentReady, introBarBtn, introReady, sContent:writeable */

(function() {
  if (typeof Vditor === "undefined") {
    console.warn("mz_vditor: Vditor is not available, skip init.");
    return;
  }

  const baseToolbar = [
    "emoji",
    "headings",
    "bold",
    "italic",
    "strike",
    "link",
    "|",
    "list",
    "ordered-list",
    "check",
    "outdent",
    "indent",
    "|",
    "quote",
    "line",
    "code",
    "inline-code",
    "insert-before",
    "insert-after",
    "|",
    "upload",
    // "record",
    "table",
    "|",
    "undo",
    "redo",
    "|",
    "fullscreen",
    "edit-mode",
    {
      name: "more",
      toolbar: [
        // "code-theme",
        // "content-theme",
        "export",
        "outline",
        "preview",
        "devtools",
        "info",
        "help",
      ],
    },
  ];

  const introToolbar = [
    "bold",
    "italic",
    "link",
    "undo",
    "redo",
  ];

  const toArray = function(value) {
    return Array.isArray(value) ? value : [];
  };

  const runReady = function(list, editor) {
    toArray(list).forEach(function(fn) {
      if (typeof fn === "function") {
        try {
          fn(editor);
        } catch (err) {
          console.error(err);
        }
      }
    });
  };

  const mapButtons = function(buttons) {
    return toArray(buttons).map(function(btn) {
      if (!btn || !btn.name) return null;
      const icon = btn.icon
        ? "<img src=\"" + btn.icon + "\" alt=\"" + btn.name + "\" style=\"width:16px;height:16px;\" />"
        : btn.name;
      return {
        name: btn.name,
        tip: btn.name,
        icon: icon,
        click: function() {
          if (typeof btn.callback === "function") {
            btn.callback.call(this);
          }
        },
      };
    }).filter(Boolean);
  };

  const buildToolbar = function(base, extra) {
    if (!base || !Array.isArray(base)) {
      return toArray(extra);
    }
    const toolbar = base.slice();
    toArray(extra).forEach(function(item) {
      toolbar.push(item);
    });
    return toolbar;
  };

  // 创建编辑器实例
  const createEditor = function(config) {
    // 获取 textarea 元素
    const $textarea = $(config.selector);
    if ($textarea.length === 0) return null;
    const textId = $textarea.attr("id");

    // 创建容器
    const containerId = textId + "_vditor";
    if (document.getElementById(containerId)) {
      return;
    }

    // 编辑器容器
    $("<div/>", { id: containerId }).insertAfter($textarea);

    // markdown 内容存储
    const metaId = textId.replace(/editor_/, "meta_md_");
    $("<textarea/>", { id: metaId, name: metaId, style: "display:none;" }).insertAfter($textarea);
    const $meta = $("#" + metaId);
    // $meta.val(config.value.trim() || "");

    // 隐藏原有 textarea
    $textarea.hide();

    // 初始化编辑器状态
    const state = { html: $textarea.val().trim(), value: config.value.trim() || "" };
    let isReady = false;

    // 构建工具栏按钮
    const toolbar = buildToolbar(config.toolbar, mapButtons(config.extraButtons));

    // 对于旧文章，如果没有 markdown 内容，则使用 html 转换
    const html2md = (_editor) => {
      if (state.value === "" && state.html !== "") {
        console.log(144);
        try {
          state.value = _editor.html2md(state.html);
          _editor.setValue(state.value);
        } catch (err) {
          console.warn("Failed to convert HTML to Markdown:", err);
          state.value = state.html;
        }
      }
    };

    const editor = new Vditor(
      containerId,
      Object.assign(
        {
          cdn: window.mzVditorCDN || "",
          // theme: 'dark',
          cache: { enable: false },
          counter: { enable: true },
          icon: ["ant", "material"][0],
          lang: "zh_CN",
          mode: "ir",
          outline: false,
          toolbar,
          toolbarConfig: { pin: true },
          value: state.value, // 初始值
          input: function() {
            sync();
          },
          after: function() {
            isReady = true;
            html2md(editor);
            runReady(config.readyList, editor);
          },
        },
        config.extend || {},
      ),
    );

    // 同步编辑器内容到 textarea 和 state
    const sync = function() {
      if (!isReady) return state.html;
      state.html = editor.getHTML();
      state.value = editor.getValue();
      $textarea.val(state.html);
      $meta.val(state.value);
      return state.html;
    };

    return { editor: editor, sync: sync, textarea: $textarea, state: state, isReady: function() { return isReady; } };
  };

  // 绑定实例到全局对象
  const bindEditorApi = function(target, instance) {
    if (!target || !instance) return;

    target.obj = instance.editor;
    target.get = function() {
      return instance.state.html;
    };
    target.put = function(str) {
      instance.state.html = str || "";
      instance.textarea.val(instance.state.html);
      if (instance.isReady && instance.isReady() && instance.editor && instance.editor.vditor && instance.editor.vditor.currentMode) {
        instance.editor.setValue(str || "");
        instance.sync();
      }
    };
    target.focus = function() {
      return instance.editor.focus();
    };
    target.insert = function(str) {
      instance.editor.insertValue(str || "");
      instance.sync();
    };
  };

  // 编辑器 API
  const editor_init = function() {
    const contentEditor = createEditor({
      selector: "#editor_content",
      value: window.mzVditorPostInfo.ContentMarkdown,
      toolbar: baseToolbar,
      extraButtons: typeof contentBarBtn === "undefined" ? [] : contentBarBtn,
      readyList: typeof contentReady === "undefined" ? [] : contentReady,
      extend: {
        height: 520,
        placeholder: "开始写作...",
        upload: {
          ...window.mzVditorUploadInfo,
          filename: (name) => {
            // 移除特殊字符，只保留字母、数字、中文和扩展名
            return name.replace(/[^(a-zA-Z0-9\u4e00-\u9fa5\.)]/g, "")
              .replace(/[\?\\/:|<>\*\[\]\(\)\$%\{\}@~]/g, "");
          },
        },
      },
    });

    const introEditor = createEditor({
      selector: "#editor_intro",
      value: window.mzVditorPostInfo.IntroMarkdown,
      toolbar: introToolbar,
      extraButtons: typeof introBarBtn === "undefined" ? [] : introBarBtn,
      readyList: typeof introReady === "undefined" ? [] : introReady,
      extend: {
        height: 260,
        placeholder: "写点简介吧...",
      },
    });

    if (contentEditor) {
      bindEditorApi(editor_api.editor.content, contentEditor);
      contentEditor.sync();
      if (typeof sContent !== "undefined") {
        sContent = editor_api.editor.content.get();
      }
    }

    if (introEditor) {
      bindEditorApi(editor_api.editor.intro, introEditor);
    }
  };

  window.getContent = function() {
    return editor_api.editor.content.get();
  };
  window.getIntro = function() {
    return editor_api.editor.intro.get();
  };
  window.setContent = function(s) {
    editor_api.editor.content.put(s);
  };
  window.setIntro = function(s) {
    editor_api.editor.intro.put(s);
  };

  window.editor_init = editor_init;
})();
