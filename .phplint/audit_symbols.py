#!/usr/bin/env python3
"""Audit identifiers used by the plugin against the php-wasm lint/smoke environment.

Unknown symbols outside known WP/plugin sets are reported; exit 1 on any hit.
"""
import re
import sys
from pathlib import Path

PLUGIN = Path("/home/user/gpspco/mojrian-club")
PHPLINT = Path("/home/user/gpspco/.phplint")

def php_scan(src: str):
    """Return the code-only view of a PHP source (strings/comments removed)."""
    i, n = 0, len(src)
    code = []
    while i < n:
        c = src[i]
        if c == '/' and i + 1 < n and src[i+1] == '*':
            j = src.find('*/', i + 2)
            i = n if j < 0 else j + 2
        elif c == '/' and i + 1 < n and src[i+1] == '/':
            j = src.find('\n', i)
            i = n if j < 0 else j
        elif c == '#':
            j = src.find('\n', i)
            i = n if j < 0 else j
        elif c == "'":
            i += 1
            while i < n:
                if src[i] == '\\':
                    i += 2
                    continue
                if src[i] == "'":
                    i += 1
                    break
                i += 1
        elif c == '"':
            i += 1
            while i < n:
                if src[i] == '\\':
                    i += 2
                    continue
                if src[i] == '"':
                    i += 1
                    break
                i += 1
        elif c == '`':
            i += 1
            while i < n:
                if src[i] == '\\':
                    i += 2
                    continue
                if src[i] == '`':
                    i += 1
                    break
                i += 1
        elif c == '<' and src.startswith('<?php', i):
            code.append('<?php')
            i += 5
        elif c == '?' and src.startswith('?>', i):
            i += 2
        else:
            code.append(c)
            i += 1
    return ''.join(code)

def strip_variables(code: str) -> str:
    code = re.sub(r'\$[A-Za-z_]\w*', ' ', code)
    code = re.sub(r'::\$\w+', ' ', code)
    return code

def collect_files():
    return sorted(p for p in PLUGIN.rglob('*')
                  if p.is_file()
                  and p.suffix in ('.php', '.js', '.css')
                  and 'node_modules' not in p.parts)

def main() -> int:
    used_fn = set()
    used_const = set()
    php_keywords = set('''if elseif else for foreach while do switch case default break continue
        return function isset unset list array echo print die exit eval include include_once require
        require_once new clone instanceof catch finally throw try match fn global static const class
        interface trait extends implements use namespace declare yield goto readonly enum abstract
        final public private protected var as insteadof or and xor empty'''.split())

    for p in collect_files():
        text = p.read_text(encoding='utf-8', errors='replace')
        if p.suffix == '.php':
            code = strip_variables(php_scan(text))
            for m in re.finditer(r'([A-Za-z_][A-Za-z0-9_]*)\s*\(', code):
                used_fn.add(m.group(1))
            for m in re.finditer(r'::([A-Za-z_][A-Za-z0-9_]*)\b', code):
                name = m.group(1)
                if name[:1].isupper() or name.isupper():
                    used_const.add(name)
                # lowercase after :: is a method — covered by plugin_fn.
        elif p.suffix == '.js':
            pass  # JS identifiers are not PHP symbols.
        elif p.suffix == '.css':
            for m in re.finditer(r'@media\s+screen\s+and\s*\(\s*min-width\s*:\s*(\d+)', text):
                used_const.add('MJC_SCREEN_' + m.group(1))

    def load_list(path: Path):
        out = set()
        if path.exists():
            for line in path.read_text().splitlines():
                line = line.strip()
                if line:
                    out.add(line)
        return out

    builtin_fn = load_list(PHPLINT / 'php_builtins.txt')
    builtin_const = load_list(PHPLINT / 'php_constants.txt')
    known_const = load_list(PHPLINT / 'wp_constants.txt')
    known_const |= {'SWITCHER', 'TEXT', 'TEXTAREA', 'NUMBER', 'SELECT', 'COLOR', 'MEDIA', 'URL', 'HEADING',
                    'DIVIDER', 'SLIDER', 'DIMENSIONS', 'POPOVER_TOGGLE', 'WYSIWYG', 'CODE', 'FONT', 'ICONS',
                    'GALLERY', 'HOVER_ANIMATION', 'ANIMATION', 'CHOOSE'}

    # From stubs (functions + classes + defines).
    for p in sorted((PHPLINT / 'stubs').rglob('*.php')):
        code = php_scan(p.read_text(encoding='utf-8', errors='replace'))
        for m in re.finditer(r'function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(', code):
            builtin_fn.add(m.group(1))
        for m in re.finditer(r'define\(\s*\'([^\']+)\'', code):
            known_const.add(m.group(1))
        for m in re.finditer(r'define\(\s*"([^"]+)"', code):
            known_const.add(m.group(1))
        for m in re.finditer(r'\bconst\s+([A-Za-z_][A-Za-z0-9_]*)\s*=', code):
            known_const.add(m.group(1))

    # From the plugin itself.
    plugin_fn = set()
    plugin_const = set()
    plugin_classes = set()
    for p in collect_files():
        if p.suffix != '.php':
            continue
        code = php_scan(p.read_text(encoding='utf-8', errors='replace'))
        for m in re.finditer(r'function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(', code):
            plugin_fn.add(m.group(1))
        for m in re.finditer(r'\bconst\s+([A-Za-z_][A-Za-z0-9_]*)\s*=', code):
            plugin_const.add(m.group(1))
        for m in re.finditer(r'\bclass\s+([A-Za-z_][A-Za-z0-9_]*)', code):
            plugin_classes.add(m.group(1))

    stub_classes = set()
    for p in sorted((PHPLINT / 'stubs').rglob('*.php')):
        code = php_scan(p.read_text(encoding='utf-8', errors='replace'))
        for m in re.finditer(r'\bclass\s+([A-Za-z_][A-Za-z0-9_]*)', code):
            stub_classes.add(m.group(1))

    result_grouping_attrs = {'total'}
    role_caps = {'mjc_manage_members', 'mjc_manage_projects', 'mjc_manage_settings', 'mjc_manage_rewards'}

    php_core = {'Throwable', 'Exception', 'Error', 'TypeError', 'ValueError', 'ParseError', 'ErrorException',
                'RuntimeException', 'LogicException', 'BadFunctionCallException', 'DomainException',
                'InvalidArgumentException', 'OutOfRangeException', 'OverflowException', 'UnderflowException',
                'UnexpectedValueException', 'Closure', 'stdClass', 'Generator', 'ArrayObject', 'ArrayIterator',
                'SplFileInfo', 'SplFileObject', 'RecursiveIteratorIterator', 'RecursiveDirectoryIterator',
                'FilesystemIterator', 'DateTime', 'DateTimeImmutable', 'DateTimeZone', 'DateInterval', 'DatePeriod',
                'JsonSerializable', 'Stringable', 'Traversable', 'Iterator', 'IteratorAggregate', 'Countable',
                'ArrayAccess', 'Serializable', 'PDO', 'PDOException', 'ReflectionClass', 'ReflectionMethod',
                'ReflectionFunction', 'ReflectionException'}
    elementor = {'Controls_Manager', 'Widget_Base', 'Group_Control_Typography', 'Group_Control_Border',
                 'Group_Control_Box_Shadow', 'Group_Control_Background', 'Repeater', 'Icons_Manager', 'Utils',
                 'Embed', 'Base_Data_Control', 'Scheme_Color', 'Scheme_Typography', 'Stack', 'Plugin'}
    wp_classes = php_core | elementor | {'WP_Error', 'WP_Post', 'WP_User', 'WP_Query', 'WP_List_Table', 'WP_REST_Response',
                  'WP_REST_Request', 'WP_REST_Server', 'WP_Widget', 'WP_CLI', 'WP_Term', 'WP_HTTP_Response'}
    wp_globals = {'wpdb', 'wp_version'}

    harmless_fn = {'easeInOutQuad', 'submit', 'forEach', 'map', 'filter', 'reduce', 'push', 'pop',
                   'split', 'join', 'slice', 'concat', 'indexOf', 'includes', 'replace', 'match',
                   'test', 'toFixed', 'parse', 'stringify', 'getItem', 'setItem', 'removeItem',
                   'addEventListener', 'querySelector', 'querySelectorAll', 'closest', 'toggle',
                   'contains', 'add', 'remove', 'createElement', 'appendChild', 'setAttribute',
                   'getAttribute', 'preventDefault', 'stopPropagation', 'focus', 'blur', 'click',
                   'requestAnimationFrame', 'setTimeout', 'clearTimeout', 'setInterval', 'clearInterval',
                   'now', 'floor', 'round', 'ceil', 'abs', 'min', 'max', 'random', 'pow', 'sqrt',
                   'log', 'exp', 'sin', 'cos', 'tan', 'atan2', 'trunc', 'sign', 'hypot', 'cbrt'}
    elementor_methods = {'add_control', 'add_responsive_control', 'add_group_control', 'add_section',
                         'start_controls_section', 'end_controls_section', 'start_controls_tabs', 'end_controls_tabs',
                         'start_controls_tab', 'end_controls_tab', 'get_settings_for_display', 'get_settings',
                         'register_widget_type', 'register', 'add_category', 'set_icon', 'get_style_depends',
                         'get_script_depends', 'get_categories', 'get_name', 'get_title', 'get_icon'}
    exception_methods = {'getMessage', 'getCode', 'getFile', 'getLine', 'getTrace', 'getPrevious', 'getTraceAsString'}
    class_like_fn = {'__construct', '__destruct', '__toString', '__get', '__set', '__isset', '__unset',
                     '__call', '__invoke', '__clone', '__wakeup', '__sleep', '__debugInfo'}

    used_classes = set()
    for p in collect_files():
        if p.suffix != '.php':
            continue
        code = php_scan(p.read_text(encoding='utf-8', errors='replace'))
        for m in re.finditer(r'\bnew\s+([A-Za-z_][A-Za-z0-9_]*)\b', code):
            used_classes.add(m.group(1))
        for m in re.finditer(r'\b([A-Za-z_][A-Za-z0-9_]*)::', code):
            used_classes.add(m.group(1))
        for m in re.finditer(r'\binstanceof\s+([A-Za-z_][A-Za-z0-9_]*)\b', code):
            used_classes.add(m.group(1))
        for m in re.finditer(r'\bcatch\s*\(\s*([A-Za-z_][A-Za-z0-9_]*)', code):
            used_classes.add(m.group(1))

    known_class_set = php_core | elementor | wp_classes | plugin_classes | stub_classes

    unknown = []
    for fn in sorted(used_fn):
        if fn in php_keywords:
            continue
        if fn in known_class_set:
            continue
        if fn in builtin_fn or fn in plugin_fn or fn in harmless_fn or fn in class_like_fn or fn in elementor_methods or fn in exception_methods:
            continue
        if fn.startswith('MJC_Stub_'):
            continue
        unknown.append(('fn', fn))
    for c in sorted(used_const):
        if c in builtin_const or c in plugin_const or c in known_const or c in result_grouping_attrs or c in role_caps:
            continue
        if c.startswith('MJC_SCREEN_'):
            continue
        unknown.append(('const', c))

    for cls in sorted(used_classes):
        if cls in known_class_set or cls in wp_globals:
            continue
        if cls.startswith('MJC_Stub_') or cls in builtin_fn:
            continue
        if cls.islower():
            continue
        unknown.append(('class', cls))

    print('=== unknown ===')
    for kind, name in unknown:
        print(f'{kind}: {name}')

    if unknown:
        print(f'TOTAL unknown: {len(unknown)}')
        return 1
    print('audit OK: no unknown symbols')
    return 0

if __name__ == '__main__':
    sys.exit(main())
