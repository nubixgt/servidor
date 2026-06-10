import React from "react";
import {
  MapPin,
  Music,
  Star,
  Building,
  Sliders,
  Mic,
  Utensils,
  UtensilsCrossed,
  Wine,
  Users,
  Grid,
  Shirt,
  Camera,
  Award,
  Sparkles,
  Activity,
  PartyPopper,
  ShieldCheck,
  ChevronRight,
  ChevronLeft,
  Check,
  RotateCcw,
  QrCode,
  Users2,
  Calendar,
  Sparkle
} from "lucide-react";

interface LucideIconProps {
  name: string;
  className?: string;
  size?: number;
}

export default function LucideIcon({ name, className = "", size = 20 }: LucideIconProps) {
  switch (name) {
    case "MapPin":
      return <MapPin className={className} size={size} />;
    case "Music":
      return <Music className={className} size={size} />;
    case "Star":
      return <Star className={className} size={size} />;
    case "Building":
      return <Building className={className} size={size} />;
    case "Sliders":
      return <Sliders className={className} size={size} />;
    case "Mic":
      return <Mic className={className} size={size} />;
    case "Utensils":
      return <Utensils className={className} size={size} />;
    case "UtensilsCrossed":
      return <UtensilsCrossed className={className} size={size} />;
    case "Wine":
      return <Wine className={className} size={size} />;
    case "Users":
      return <Users className={className} size={size} />;
    case "Grid":
      return <Grid className={className} size={size} />;
    case "Shirt":
      return <Shirt className={className} size={size} />;
    case "Camera":
      return <Camera className={className} size={size} />;
    case "Award":
      return <Award className={className} size={size} />;
    case "Sparkles":
      return <Sparkles className={className} size={size} />;
    case "Activity":
      return <Activity className={className} size={size} />;
    case "PartyPopper":
      return <PartyPopper className={className} size={size} />;
    case "ShieldCheck":
      return <ShieldCheck className={className} size={size} />;
    case "ChevronRight":
      return <ChevronRight className={className} size={size} />;
    case "ChevronLeft":
      return <ChevronLeft className={className} size={size} />;
    case "Check":
      return <Check className={className} size={size} />;
    case "RotateCcw":
      return <RotateCcw className={className} size={size} />;
    case "QrCode":
      return <QrCode className={className} size={size} />;
    case "Users2":
      return <Users2 className={className} size={size} />;
    case "Calendar":
      return <Calendar className={className} size={size} />;
    case "Sparkle":
      return <Sparkle className={className} size={size} />;
    default:
      return <Star className={className} size={size} />;
  }
}
