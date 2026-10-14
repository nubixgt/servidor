import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Fondo de paisaje con velo (equivale a .page-landscape + .page-veil).
class AppBackground extends StatelessWidget {
  final Widget child;
  final bool login;
  const AppBackground({super.key, required this.child, this.login = false});

  @override
  Widget build(BuildContext context) {
    return Stack(fit: StackFit.expand, children: [
      Image.asset('assets/images/fondo.jpg', fit: BoxFit.cover, alignment: Alignment.center),
      DecoratedBox(
        decoration: BoxDecoration(
          gradient: login
              ? const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xB5073B36), Color(0x6B092D2B), Color(0xB30B3429)], stops: [0, .45, 1])
              : const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0x38042B22), Color(0x52DAEDDC), Color(0x8FDAEDDC)], stops: [0, .45, 1]),
        ),
      ),
      child,
    ]);
  }
}

/// Tarjeta de vidrio (equivale a .panel).
class Panel extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;
  final double radius;
  final Gradient? gradient;
  final Color? color;
  const Panel({super.key, required this.child, this.padding = const EdgeInsets.all(20), this.radius = 21, this.gradient, this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        gradient: color != null ? null : (gradient ?? const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xEDFFFFFF), Color(0xDBF5FBF6)])),
        color: color,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: Colors.white),
        boxShadow: const [BoxShadow(color: Color(0x1A0E4A36), blurRadius: 26, offset: Offset(0, 8))],
      ),
      child: child,
    );
  }
}

class PanelHead extends StatelessWidget {
  final String title;
  final String? subtitle;
  final Widget? trailing;
  const PanelHead({super.key, required this.title, this.subtitle, this.trailing});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
            if (subtitle != null) Padding(padding: const EdgeInsets.only(top: 4), child: Text(subtitle!, style: const TextStyle(fontSize: 11, color: AppColors.muted))),
          ]),
        ),
        ?trailing,
      ]),
    );
  }
}

enum BadgeKind { green, amber, red, blue, gray, purple }

class AppBadge extends StatelessWidget {
  final String text;
  final BadgeKind kind;
  const AppBadge(this.text, {super.key, this.kind = BadgeKind.green});

  @override
  Widget build(BuildContext context) {
    final (fg, bg) = switch (kind) {
      BadgeKind.green => (const Color(0xFF13774F), const Color(0xFFE6F5EE)),
      BadgeKind.amber => (const Color(0xFFAA6B17), const Color(0xFFFFF1D5)),
      BadgeKind.red => (const Color(0xFFBD4E3C), const Color(0xFFFFF0EA)),
      BadgeKind.blue => (const Color(0xFF39789B), const Color(0xFFE6F2FA)),
      BadgeKind.gray => (const Color(0xFF75848B), const Color(0xFFEDF1F2)),
      BadgeKind.purple => (const Color(0xFF7D5A9D), const Color(0xFFF0EAF8)),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(7)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Container(width: 5, height: 5, decoration: BoxDecoration(color: fg, shape: BoxShape.circle)),
        const SizedBox(width: 5),
        Text(text, style: TextStyle(color: fg, fontSize: 11, fontWeight: FontWeight.w600)),
      ]),
    );
  }
}

BadgeKind kindDeNivel(int index) => const [BadgeKind.green, BadgeKind.amber, BadgeKind.red, BadgeKind.purple][index];

/// Botón principal (gradiente verde) y secundario (blanco).
class AppButton extends StatelessWidget {
  final String label;
  final IconData? icon;
  final VoidCallback? onPressed;
  final bool primary;
  final bool danger;
  final bool expand;
  final bool loading;
  final IconData? trailingIcon;
  const AppButton(this.label, {super.key, this.icon, this.onPressed, this.primary = false, this.danger = false, this.expand = false, this.loading = false, this.trailingIcon});

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null && !loading;
    final fg = primary ? Colors.white : (danger ? AppColors.red : AppColors.navy);
    final content = Row(mainAxisSize: expand ? MainAxisSize.max : MainAxisSize.min, mainAxisAlignment: MainAxisAlignment.center, children: [
      if (loading)
        SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: fg))
      else if (icon != null)
        Icon(icon, size: 19, color: fg),
      if (loading || icon != null) const SizedBox(width: 8),
      Flexible(child: Text(label, overflow: TextOverflow.ellipsis, style: TextStyle(color: fg, fontSize: 13, fontWeight: FontWeight.w600))),
      if (trailingIcon != null) ...[const SizedBox(width: 8), Icon(trailingIcon, size: 19, color: fg)],
    ]);
    return Opacity(
      opacity: enabled || loading ? 1 : .55,
      child: Material(
        color: Colors.transparent,
        child: Ink(
          decoration: BoxDecoration(
            gradient: primary ? const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFF08A766), Color(0xFF00734E)]) : null,
            color: primary ? null : (danger ? const Color(0xFFFFF2EE) : Colors.white),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: primary ? const Color(0xFF32B782) : (danger ? const Color(0xFFF2D3CB) : const Color(0xFFD2E4DF))),
            boxShadow: primary ? const [BoxShadow(color: Color(0x2600754F), blurRadius: 18, offset: Offset(0, 5))] : null,
          ),
          child: InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: enabled ? onPressed : null,
            child: Container(constraints: const BoxConstraints(minHeight: 44), padding: const EdgeInsets.symmetric(horizontal: 17, vertical: 11), child: content),
          ),
        ),
      ),
    );
  }
}

class IconBtn extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onPressed;
  final Color color;
  final String? tooltip;
  const IconBtn(this.icon, {super.key, this.onPressed, this.color = AppColors.green, this.tooltip});

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip ?? '',
      child: InkWell(
        borderRadius: BorderRadius.circular(11),
        onTap: onPressed,
        child: Opacity(
          opacity: onPressed == null ? .45 : 1,
          child: Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(color: const Color(0xFFF0F7F4), borderRadius: BorderRadius.circular(11), border: Border.all(color: AppColors.line)),
            child: Icon(icon, size: 18, color: color),
          ),
        ),
      ),
    );
  }
}

class StatCard extends StatelessWidget {
  final String title;
  final String value;
  final String? unit;
  final String? foot;
  final IconData icon;
  final int index;
  final Color? footColor;
  const StatCard({super.key, required this.title, required this.value, this.unit, this.foot, required this.icon, this.index = 0, this.footColor});

  static const _colors = [
    (Color(0xFF19875B), Color(0xFFE1F1E8)),
    (Color(0xFF317F9F), Color(0xFFE6F2F6)),
    (Color(0xFFA97B27), Color(0xFFFAF0D9)),
    (Color(0xFFBB694D), Color(0xFFFFEDE3)),
  ];

  @override
  Widget build(BuildContext context) {
    final (fg, bg) = _colors[index % 4];
    return Panel(
      radius: 18,
      padding: const EdgeInsets.fromLTRB(16, 16, 12, 14),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Expanded(child: Padding(padding: const EdgeInsets.only(top: 2), child: Text(title, style: const TextStyle(fontSize: 11, color: Color(0xFF55716F), height: 1.3)))),
          Container(width: 32, height: 32, decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(11)), child: Icon(icon, size: 17, color: fg)),
        ]),
        const SizedBox(height: 6),
        Row(crossAxisAlignment: CrossAxisAlignment.baseline, textBaseline: TextBaseline.alphabetic, children: [
          Flexible(child: FittedBox(fit: BoxFit.scaleDown, alignment: Alignment.centerLeft, child: Text(value, style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w600, letterSpacing: -1)))),
          if (unit != null) Padding(padding: const EdgeInsets.only(left: 3), child: Text(unit!, style: const TextStyle(fontSize: 14))),
        ]),
        if (foot != null) Padding(padding: const EdgeInsets.only(top: 6), child: Text(foot!, style: TextStyle(fontSize: 10, color: footColor ?? const Color(0xFF6D8380)), maxLines: 2, overflow: TextOverflow.ellipsis)),
      ]),
    );
  }
}

/// Dos estadísticas por fila (la web las muestra en 2 columnas en móvil).
class StatsGrid extends StatelessWidget {
  final List<StatCard> cards;
  const StatsGrid({super.key, required this.cards});

  @override
  Widget build(BuildContext context) {
    final rows = <Widget>[];
    for (var i = 0; i < cards.length; i += 2) {
      rows.add(IntrinsicHeight(
        child: Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Expanded(child: cards[i]),
          const SizedBox(width: 10),
          Expanded(child: i + 1 < cards.length ? cards[i + 1] : const SizedBox()),
        ]),
      ));
      if (i + 2 < cards.length) rows.add(const SizedBox(height: 10));
    }
    return Column(children: rows);
  }
}

/// Encabezado de pantalla sobre el fondo (equivale a .content-head).
class ContentHead extends StatelessWidget {
  final String title;
  final String subtitle;
  final Widget? action;
  const ContentHead({super.key, required this.title, required this.subtitle, this.action});

  @override
  Widget build(BuildContext context) {
    const shadow = [Shadow(color: Color(0x59042B22), blurRadius: 8)];
    return Padding(
      padding: const EdgeInsets.fromLTRB(2, 0, 2, 16),
      child: Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700, letterSpacing: -.5, color: Colors.white, shadows: shadow)),
            const SizedBox(height: 3),
            Text(subtitle, style: const TextStyle(fontSize: 12, color: Color(0xFFE4F1EA), shadows: shadow)),
          ]),
        ),
        if (action != null) ...[const SizedBox(width: 10), action!],
      ]),
    );
  }
}

class FieldLabel extends StatelessWidget {
  final String text;
  const FieldLabel(this.text, {super.key});
  @override
  Widget build(BuildContext context) =>
      Padding(padding: const EdgeInsets.only(bottom: 8), child: Text(text, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.label)));
}

/// Etiqueta + control, con espacio inferior.
class LabeledField extends StatelessWidget {
  final String label;
  final Widget child;
  final String? hint;
  const LabeledField({super.key, required this.label, required this.child, this.hint});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        FieldLabel(label),
        child,
        if (hint != null) Padding(padding: const EdgeInsets.only(top: 4), child: Text(hint!, style: const TextStyle(fontSize: 11, color: AppColors.muted))),
      ]),
    );
  }
}

class EmptyState extends StatelessWidget {
  final String text;
  const EmptyState(this.text, {super.key});
  @override
  Widget build(BuildContext context) =>
      Padding(padding: const EdgeInsets.all(36), child: Center(child: Text(text, textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF748A7B), fontSize: 13))));
}

String fmtNum(num? v, [int d = 2]) {
  if (v == null) return '0';
  final s = v.toStringAsFixed(d);
  final t = s.contains('.') ? s.replaceFirst(RegExp(r'0+$'), '').replaceFirst(RegExp(r'\.$'), '') : s;
  final parts = t.split('.');
  final ent = parts[0].replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => ',');
  return parts.length > 1 ? '$ent.${parts[1]}' : ent;
}
