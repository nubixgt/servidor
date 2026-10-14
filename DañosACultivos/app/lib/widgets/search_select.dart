import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

String _norm(String s) {
  const from = 'áàäâéèëêíìïîóòöôúùüûñÁÉÍÓÚÑ';
  const to = 'aaaaeeeeiiiioooouuuunAEIOUN';
  var r = s;
  for (var i = 0; i < from.length; i++) {
    r = r.replaceAll(from[i], to[i]);
  }
  return r.toLowerCase();
}

/// Campo con buscador sobre un listado (equivale a SearchSelect.vue).
/// Abre una hoja inferior con caja de búsqueda; solo permite elegir valores del listado.
class SearchSelectField extends FormField<String> {
  SearchSelectField({
    super.key,
    required String value,
    required List<String> options,
    required ValueChanged<String> onChanged,
    String placeholder = 'Buscar…',
    String title = 'Seleccionar',
    bool required = true,
    bool enabled = true,
    String? requiredMessage,
  }) : super(
          initialValue: value,
          validator: (v) => required && (v == null || v.isEmpty) ? (requiredMessage ?? 'Campo obligatorio') : null,
          builder: (state) {
            final context = state.context;
            final hasValue = (state.value ?? '').isNotEmpty;
            return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              InkWell(
                borderRadius: BorderRadius.circular(10),
                onTap: enabled
                    ? () async {
                        final r = await showModalBottomSheet<String>(
                          context: context,
                          isScrollControlled: true,
                          backgroundColor: Colors.transparent,
                          builder: (_) => _SearchSheet(title: title, options: options, selected: state.value ?? '', placeholder: placeholder),
                        );
                        if (r != null) {
                          state.didChange(r);
                          onChanged(r);
                        }
                      }
                    : null,
                child: InputDecorator(
                  decoration: InputDecoration(
                    errorText: state.errorText,
                    suffixIcon: const Icon(Icons.search, size: 20, color: Color(0xFF81978D)),
                    enabled: enabled,
                  ),
                  child: Text(hasValue ? state.value! : placeholder,
                      maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 14, color: hasValue ? AppColors.navy : const Color(0xFF98AAA4))),
                ),
              ),
            ]);
          },
        );

  @override
  FormFieldState<String> createState() => _SearchSelectState();
}

class _SearchSelectState extends FormFieldState<String> {
  @override
  void didUpdateWidget(covariant SearchSelectField oldWidget) {
    super.didUpdateWidget(oldWidget);
    final nv = widget.initialValue ?? '';
    if (nv != (value ?? '')) setValue(nv);
  }
}

class _SearchSheet extends StatefulWidget {
  final String title;
  final List<String> options;
  final String selected;
  final String placeholder;
  const _SearchSheet({required this.title, required this.options, required this.selected, required this.placeholder});

  @override
  State<_SearchSheet> createState() => _SearchSheetState();
}

class _SearchSheetState extends State<_SearchSheet> {
  String q = '';

  @override
  Widget build(BuildContext context) {
    final nq = _norm(q.trim());
    final list = nq.isEmpty ? widget.options : widget.options.where((o) => _norm(o).contains(nq)).toList();
    final h = MediaQuery.of(context).size.height;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        height: h * .75,
        decoration: const BoxDecoration(color: Color(0xFFFAFDF9), borderRadius: BorderRadius.vertical(top: Radius.circular(23))),
        child: Column(children: [
          const SizedBox(height: 10),
          Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(4))),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 8),
            child: Row(children: [
              Expanded(child: Text(widget.title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700))),
              IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 10),
            child: TextField(
              autofocus: true,
              onChanged: (v) => setState(() => q = v),
              decoration: InputDecoration(hintText: widget.placeholder, prefixIcon: const Icon(Icons.search, size: 20)),
            ),
          ),
          Expanded(
            child: list.isEmpty
                ? const Center(child: Text('Sin resultados', style: TextStyle(color: AppColors.muted)))
                : ListView.builder(
                    itemCount: list.length,
                    itemBuilder: (_, i) {
                      final o = list[i];
                      final sel = o == widget.selected;
                      return ListTile(
                        dense: true,
                        title: Text(o, style: TextStyle(fontWeight: sel ? FontWeight.w700 : FontWeight.w400)),
                        trailing: sel ? const Icon(Icons.check, color: AppColors.green) : null,
                        onTap: () => Navigator.pop(context, o),
                      );
                    },
                  ),
          ),
        ]),
      ),
    );
  }
}

/// Botones de selección única (ej. Sí / No). Solo se puede elegir uno.
class ChoiceButtons extends FormField<String> {
  ChoiceButtons({
    super.key,
    required String value,
    required List<String> options,
    required ValueChanged<String> onChanged,
    bool required = true,
  }) : super(
          initialValue: value,
          validator: (v) => required && (v == null || v.isEmpty) ? 'Selecciona una opción' : null,
          builder: (state) {
            return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                for (var i = 0; i < options.length; i++) ...[
                  if (i > 0) const SizedBox(width: 8),
                  Expanded(
                    child: Semantics(
                      button: true,
                      selected: state.value == options[i],
                      child: InkWell(
                        borderRadius: BorderRadius.circular(12),
                        onTap: () {
                          state.didChange(options[i]);
                          onChanged(options[i]);
                        },
                        child: Container(
                          height: 48,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            gradient: state.value == options[i] ? const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFF08A766), Color(0xFF00734E)]) : null,
                            color: state.value == options[i] ? null : Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: state.hasError ? AppColors.red : (state.value == options[i] ? const Color(0xFF32B782) : const Color(0xFFD2E4DF))),
                          ),
                          child: Text(options[i], style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: state.value == options[i] ? Colors.white : AppColors.navy)),
                        ),
                      ),
                    ),
                  ),
                ],
              ]),
              if (state.hasError) Padding(padding: const EdgeInsets.only(top: 6, left: 4), child: Text(state.errorText!, style: const TextStyle(fontSize: 12, color: AppColors.red))),
            ]);
          },
        );

  @override
  FormFieldState<String> createState() => _ChoiceState();
}

class _ChoiceState extends FormFieldState<String> {
  @override
  void didUpdateWidget(covariant ChoiceButtons oldWidget) {
    super.didUpdateWidget(oldWidget);
    final nv = widget.initialValue ?? '';
    if (nv != (value ?? '')) setValue(nv);
  }
}

/// Lista desplegable simple (equivale a SelectField.vue).
class SelectBox extends StatelessWidget {
  final String value;
  final List<String> options;
  final ValueChanged<String> onChanged;
  final String placeholder;
  final bool required;
  const SelectBox({super.key, required this.value, required this.options, required this.onChanged, this.placeholder = 'Seleccione...', this.required = false});

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<String>(
      key: ValueKey('$value|${options.length}'),
      initialValue: options.contains(value) ? value : null,
      isExpanded: true,
      hint: Text(placeholder, style: const TextStyle(fontSize: 14, color: Color(0xFF98AAA4))),
      items: options.map((o) => DropdownMenuItem(value: o, child: Text(o, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 14)))).toList(),
      onChanged: (v) => onChanged(v ?? ''),
      validator: required ? (v) => v == null || v.isEmpty ? 'Campo obligatorio' : null : null,
      icon: const Icon(Icons.keyboard_arrow_down),
    );
  }
}
