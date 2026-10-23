import * as React from "react"

import {
  Dialog as BaseDialog,
  DialogClose as BaseDialogClose,
  DialogContent as BaseDialogContent,
  DialogDescription as BaseDialogDescription,
  DialogFooter as BaseDialogFooter,
  DialogHeader as BaseDialogHeader,
  DialogTitle as BaseDialogTitle,
  DialogTrigger as BaseDialogTrigger,
} from "@/components/ui/dialog"
import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerDescription,
  DrawerTitle,
  DrawerTrigger,
} from "@/components/ui/drawer"
import { useIsMobile } from "@/hooks/use-mobile"
import { cn } from "@/lib/utils"

/**
 * Drop-in replacement for the Dialog primitives: a centred dialog on desktop and
 * an iOS-style bottom drawer (drag to dismiss) on phones.
 */
const PhoneContext = React.createContext(false)

function Dialog({
  children,
  ...props
}: React.ComponentProps<typeof BaseDialog>) {
  const isPhone = useIsMobile()

  return (
    <PhoneContext.Provider value={isPhone}>
      {isPhone ? (
        <Drawer
          open={props.open}
          defaultOpen={props.defaultOpen}
          onOpenChange={props.onOpenChange}
        >
          {children}
        </Drawer>
      ) : (
        <BaseDialog {...props}>{children}</BaseDialog>
      )}
    </PhoneContext.Provider>
  )
}

function DialogTrigger(props: React.ComponentProps<typeof BaseDialogTrigger>) {
  return React.useContext(PhoneContext) ? (
    <DrawerTrigger {...props} />
  ) : (
    <BaseDialogTrigger {...props} />
  )
}

function DialogContent({
  className,
  overlayClassName,
  children,
  phone = "sheet",
  ...props
}: React.ComponentProps<typeof BaseDialogContent> & {
  overlayClassName?: string
  /**
   * How the content presents on phones: a padded bottom sheet ("sheet"), a bottom sheet whose
   * content manages its own padding ("flush"), or an immersive full-screen sheet ("fullscreen").
   */
  phone?: "sheet" | "flush" | "fullscreen"
}) {
  if (React.useContext(PhoneContext)) {
    return (
      <DrawerContent
        className={
          phone === "fullscreen"
            ? "h-[100svh] max-h-[100svh] rounded-none border-0"
            : undefined
        }
      >
        <div
          data-vaul-no-drag={phone === "fullscreen" ? "" : undefined}
          className={cn(
            "flex min-h-0 flex-1 flex-col overflow-y-auto",
            phone === "sheet" &&
              "gap-4 px-5 pt-2 pb-[calc(1.25rem+env(safe-area-inset-bottom))]",
            phone !== "sheet" && "pb-[env(safe-area-inset-bottom)]"
          )}
        >
          {children}
        </div>
      </DrawerContent>
    )
  }

  return (
    <BaseDialogContent
      className={className}
      overlayClassName={overlayClassName}
      {...props}
    >
      {children}
    </BaseDialogContent>
  )
}

function DialogHeader({ className, ...props }: React.ComponentProps<"div">) {
  return <BaseDialogHeader className={cn("text-left", className)} {...props} />
}

function DialogFooter(props: React.ComponentProps<"div">) {
  return <BaseDialogFooter {...props} />
}

function DialogTitle(props: React.ComponentProps<typeof BaseDialogTitle>) {
  return React.useContext(PhoneContext) ? (
    <DrawerTitle {...props} />
  ) : (
    <BaseDialogTitle {...props} />
  )
}

function DialogDescription(
  props: React.ComponentProps<typeof BaseDialogDescription>
) {
  return React.useContext(PhoneContext) ? (
    <DrawerDescription {...props} />
  ) : (
    <BaseDialogDescription {...props} />
  )
}

function DialogClose(props: React.ComponentProps<typeof BaseDialogClose>) {
  return React.useContext(PhoneContext) ? (
    <DrawerClose {...props} />
  ) : (
    <BaseDialogClose {...props} />
  )
}

export {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
}
